<?php

namespace App\Actions\Transaction;

use Carbon\Carbon;
use App\Enums\TransactionType;
use App\DTOs\Transaction\UpdateTransactionData;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionBalanceService;
use App\Services\DailyAllowanceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class UpdateTransactionAction
{
    public function __construct(
        private TransactionBalanceService $transactionBalanceService,
        private DailyAllowanceCalculator $dailyAllowanceCalculator,
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $data): Transaction
    {
        return DB::transaction(function () use ($user, $transaction, $data) {
            if ($user->id !== $transaction->user_id) {
                throw new UnauthorizedException('You are not authorized to perform this action.');
            }

            $payload = [];
            if ($data->nameProvided) $payload['name'] = $data->name;
            if ($data->noteProvided) $payload['note'] = $data->note;
            if ($data->amountProvided) $payload['amount'] = $data->amount;
            if ($data->typeProvided) $payload['type'] = $data->type;
            if ($data->categoryIdProvided) $payload['category_id'] = $data->categoryId;
            if ($data->transactionDateProvided) $payload['transaction_date'] = $data->transactionDate?->toDateString();

            $finalType = $payload['type'] ?? $transaction->type;
            $finalAmount = (string) ($payload['amount'] ?? $transaction->amount);
            $finalCategoryId = $payload['category_id'] ?? $transaction->category_id;

            $finalCategory = Category::query()->where('user_id', $user->id)->findOrFail($finalCategoryId);
            if ($finalType !== $finalCategory->type) {
                throw ValidationException::withMessages(['categoryId' => 'Category should match category type.']);
            }

            $affectsBalance = $data->amountProvided 
                || $data->typeProvided 
                || $data->transactionDateProvided 
                || $data->categoryIdProvided;

            if ($affectsBalance) {
                if ($data->amountProvided && $finalType === TransactionType::EXPENSE) {
                    $dailyAllowance = $user->daily_allowance;
                    $balance = $user->balance;
                    if (bccomp($finalAmount, $dailyAllowance, 2) === 1 &&
                        bccomp($finalAmount, $balance, 2) === 1) {
                        throw ValidationException::withMessages([
                            'amount' => 'Updated expense exceeds current balance.',
                        ]);
                    }
                }

                if ($data->typeProvided && $data->type !== $transaction->type) {
                    throw ValidationException::withMessages([
                        'type' => 'Changing transaction type is not allowed.',
                    ]);
                }

                if ($data->transactionDateProvided && $data->transactionDate->greaterThan(Carbon::today())) {
                    throw ValidationException::withMessages([
                        'transaction_date' => 'Transaction date cannot be in the future.',
                    ]);
                }

                // reapply balance
                $newBalance = $this->transactionBalanceService->reapplyUpdatedTransaction(
                    (string) $user->balance,
                    $transaction,
                    $finalType,
                    $finalAmount,
                );

                $user->update(['balance' => $newBalance]);

                // trigger recalculation allowance
                if ($data->amountProvided) {
                    $this->dailyAllowanceCalculator->recalculate($user, $newBalance);
                }
            }

            $transaction->update($payload);

            return $transaction->refresh();
        });

    }
}
