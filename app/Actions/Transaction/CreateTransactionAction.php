<?php

namespace App\Actions\Transaction;

use App\DTOs\Transaction\CreateTransactionData;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionBalanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class CreateTransactionAction
{
    public function __construct(
        private TransactionBalanceService $transactionBalanceService
    ) {}

    /**
     * @throws Throwable
     */
    public function execute(User $user, CreateTransactionData $data): Transaction
    {

        return DB::transaction(function () use ($user, $data) {
            // validate consistency between transaction type and category type
            $category = Category::query()->where('user_id', '=', $user->id)->findOrFail($data->categoryId);

            if ($data->type !== $category->type) {
                throw ValidationException::withMessages(['categoryId' => 'Category should match category type.']);
            }

            $transaction = Transaction::query()->create([
                'user_id' => $user->id,
                'category_id' => $data->categoryId,
                'name' => $data->name,
                'amount' => $data->amount,
                'type' => $data->type->value,
                'note' => $data->note,
                'transaction_date' => $data->transactionDate->toDateString(),
            ]);

            $user->update([
                'balance' => $this->transactionBalanceService->applyTransaction(
                    (string) $user->balance,
                    $data->type,
                    (string) $data->amount,
                ),
            ]);

            return $transaction->refresh();
        });
    }
}
