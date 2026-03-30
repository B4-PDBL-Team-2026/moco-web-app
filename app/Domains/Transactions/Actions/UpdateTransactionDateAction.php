<?php

namespace App\Domains\Transactions\Actions;

use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateTransactionDateAction
{
    /**
     * Rule 26: Date may be changed up to today.
     *          Future dates are rejected — those belong to fixed cost functionality.
     *          No allowance recalculation is triggered by a date change.
     *
     * @throws ValidationException|Throwable
     */
    public function execute(User $user, Transaction $transaction, CarbonImmutable $newDate): Transaction
    {
        return DB::transaction(function () use ($transaction, $newDate) {
            $today = CarbonImmutable::today();

            // Rule 26: reject future dates
            if ($newDate->startOfDay()->greaterThan($today)) {
                throw ValidationException::withMessages([
                    'transactionDate' => ['Transaction date cannot be set to a future date.'],
                ]);
            }

            // Rule 26: no recalculation triggered — only update the date
            $transaction->update([
                'transaction_date' => $newDate->toDateString(),
            ]);

            return $transaction->refresh();
        });
    }
}
