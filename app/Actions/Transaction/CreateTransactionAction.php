<?php

namespace App\Actions\Transaction;

use App\DTOs\Transaction\CreateTransactionData;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionBalanceService;
use App\Services\DailyAllowanceCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

readonly class CreateTransactionAction
{
    public function __construct(
        private TransactionBalanceService $transactionBalanceService,
        private DailyAllowanceCalculator $dailyAllowanceCalcualtor //tambahan baru(1)
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

            $newBalance = $this->transactionBalanceService->applyTransaction(
                (string) $user->balance,
                $data->type,
                (string) $data->amount,
            );

            $user->update([
                'balance' => $newBalance
            ]);

            if ($data->type === TransactionType::INCOME) {
                $this->dailyAllowanceCalcualtor->recalculate($user, $newBalance);
            }

            if ($data->type === TransactionType::EXPENSE){
              $dailyAllowance = $user->daily_allowance;
              $balance = $user->balance;
              
              // jika expense > daily allowance
              if (bccomp((string)$data->amount, (string)$dailyAllowance, 2) === 1) {
                        // cek apakah masih ≤ balance
                if (bccomp((string)$data->amount, (string)$balance, 2) === 1) {
                    throw ValidationException::withMessages([
                        'amount' => 'Expense exceeds current balance.',
                    ]);
                }
                // kalau expense > allowance tapi ≤ balance → tetap boleh
              }
            }

            return $transaction->refresh();
        });
    }
}
