<?php

namespace App\Domains\FixedCost\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\FixedCost\DTOs\UpdateFixedCostOccurrenceMetadataData;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Updates non-financial metadata on a fixed cost occurrence.
 *
 * Business Rule §15:
 * Changing metadata such as name or note does NOT trigger any recalculation
 * of daily allowance, balance, or reserved cost. This action enforces that
 * by never touching the snapshot or any financial field.
 *
 * Accepted fields: name, note (future-extendable).
 * Rejected fields (handled by other actions): amount, status, due_date, category.
 */
final readonly class UpdateFixedCostOccurrenceMetadataAction
{
    /**
     * @param  int  $userId  Ownership guard.
     * @param  int  $occurrenceId  The occurrence to update.
     * @param  UpdateFixedCostOccurrenceMetadataData  $data  Associative array of allowed metadata fields.
     *                                                       Supported keys: 'name', 'note'.
     *
     * @throws Throwable
     */
    public function execute(int $userId, int $occurrenceId, UpdateFixedCostOccurrenceMetadataData $data): FixedCostOccurrence
    {
        if (isset($data->name) && trim($data->name) === '') {
            throw new BusinessRuleException('error.validation.empty');
        }

        return DB::transaction(function () use ($userId, $occurrenceId, $data): FixedCostOccurrence {
            $occurrence = FixedCostOccurrence::with(['transaction', 'category'])
                ->where('user_id', $userId)
                ->findOrFail($occurrenceId);

            $updates = [];

            if ($data->name) {
                $updates['name'] = $data->name;
            }

            if ($data->note) {
                $updates['note'] = $data->note;
            }

            if (! empty($updates)) {
                $occurrence->update($updates);

                if ($occurrence->transaction) {
                    $transactionUpdates = [];

                    if ($data->name) {
                        $transactionUpdates['name'] = $data->name;
                    }

                    if ($data->note) {
                        $transactionUpdates['note'] = $data->note;
                    }

                    if (! empty($transactionUpdates)) {
                        $occurrence->transaction->update($transactionUpdates);
                    }
                }
            }

            return $occurrence->refresh();
        });
    }
}
