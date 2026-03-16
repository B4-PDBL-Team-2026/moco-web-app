<?php

namespace App\Actions\Transaction;

use App\DTOs\Transaction\UpdateTransactionData;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class UpdateTransactionAction
{
    public function __construct(
        private TransactionBalanceService $transactionBalanceService,
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

            if ($data->nameProvided) {
                $payload['name'] = $data->name;
            }

            if ($data->amountProvided) {
                $payload['amount'] = $data->amount;
            }

            if ($data->typeProvided) {
                $payload['type'] = $data->type;
            }

            if ($data->categoryIdProvided) {
                $payload['category_id'] = $data->categoryId;
            }

            if ($data->noteProvided) {
                $payload['note'] = $data->note;
            }

            if ($data->transactionDateProvided) {
                $payload['transaction_date'] = $data->transactionDate?->toDateString();
            }

            $finalType = $payload['type'] ?? $transaction->type;

            $finalAmount = (string) ($payload['amount'] ?? $transaction->amount);

            $finalCategoryId = array_key_exists('category_id', $payload)
                ? $payload['category_id']
                : $transaction->category_id;

            $finalCategory = Category::query()->where('user_id', '=', $user->id)
                ->findOrFail($finalCategoryId);

            if ($finalType !== $finalCategory->type) {
                throw ValidationException::withMessages(['categoryId' => 'Category should match category type.']);
            }

            $newBalance = $this->transactionBalanceService->reapplyUpdatedTransaction(
                currentBalance: (string) $user->balance,
                oldTransaction: $transaction,
                newType: $finalType,
                newAmount: $finalAmount,
            );

            $transaction->update($payload);

            $user->update([
                'balance' => $newBalance,
            ]);

            return $transaction->refresh();
        });
    }
}
