<?php

namespace App\Domains\Transactions\Actions;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateTransactionMetadataAction
{
    /**
     * Rule 27: Metadata changes (name, note, category) do NOT trigger
     *          balance or allowance recalculation.
     *
     * @throws Throwable
     */
    public function execute(User $user, Transaction $transaction, array $fields): Transaction
    {
        return DB::transaction(function () use ($transaction, $fields) {
            $updatePayload = [];

            if (array_key_exists('name', $fields)) {
                $updatePayload['name'] = $fields['name'];
            }

            if (array_key_exists('note', $fields)) {
                $updatePayload['note'] = $fields['note'];
            }

            if (array_key_exists('categoryId', $fields)) {
                $updatePayload['category_id'] = $fields['categoryId'];
            }

            if (array_key_exists('categoryType', $fields)) {
                $updatePayload['category_type'] = $fields['categoryType'];
            }

            if (! empty($updatePayload)) {
                $transaction->update($updatePayload);
            }

            // Rule 27: intentionally no recalculation here
            return $transaction->refresh();
        });
    }
}
