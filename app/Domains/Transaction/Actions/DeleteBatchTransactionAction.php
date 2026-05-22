<?php

namespace App\Domains\Transaction\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\TransactionBatch;
use App\Domains\Transaction\Services\TransactionValidator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteBatchTransactionAction
{
    public function __construct(
        private readonly TransactionBalanceService $transactionBalanceService,
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
        private readonly TransactionValidator $transactionValidator,
    ) {}

    /**
     * Expense transactions can always be deleted.
     * Income transactions can only be deleted if balance - netIncome >= 0.
     * Net income = total income items - total expense items in the batch.
     *
     * @throws Throwable
     * @throws BusinessRuleException
     */
    public function execute(int $userId, int $transactionBatchId): void
    {
        $batch = TransactionBatch::query()
            ->where('user_id', '=', $userId)
            ->where('id', '=', $transactionBatchId)
            ->with('transactions')
            ->firstOrFail();

        DB::transaction(function () use ($userId, $batch) {
            $netIncome = $batch->transactions->reduce(function (string $carry, $tx) {
                return $this->transactionBalanceService->applyTransaction(
                    currentBalance: $carry,
                    type: TransactionType::from($tx->type instanceof \BackedEnum ? $tx->type->value : $tx->type),
                    amount: (string) $tx->amount,
                );
            }, '0.00');

            if (bccomp($netIncome, '0.00', 2) > 0) {
                $snapshot = UserBudgetSnapshot::query()
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balanceAfterDelete = $this->transactionBalanceService->reverseTransaction(
                    currentBalance: (string) $snapshot->current_balance,
                    type: TransactionType::INCOME, // net positif = efeknya seperti hapus income
                    amount: $netIncome,
                );

                $this->transactionValidator->ensureSufficientBalance($balanceAfterDelete, '0.00');
            }

            $batch->transactions()->delete();
            $batch->delete();

            $this->recalculateBudgetSnapshotAction->execute(
                userId: $userId,
                now: CarbonImmutable::now(),
            );
        });
    }
}
