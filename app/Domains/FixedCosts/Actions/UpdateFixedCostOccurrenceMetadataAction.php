<?php

namespace App\Domains\FixedCosts\Actions;

use App\Models\FixedCostOccurrence;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

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
     * @param  array  $metadata  Associative array of allowed metadata fields.
     *                           Supported keys: 'name', 'note'.
     *
     * @throws ModelNotFoundException If the occurrence does not belong to the user.
     * @throws InvalidArgumentException If an unsupported field is provided or name is empty.
     */
    public function execute(int $userId, int $occurrenceId, array $metadata): void
    {
        $allowedFields = ['name', 'note'];

        $disallowed = array_diff(array_keys($metadata), $allowedFields);

        if (! empty($disallowed)) {
            throw new InvalidArgumentException(
                'The following fields cannot be updated via metadata action: '.implode(', ', $disallowed)
            );
        }

        if (isset($metadata['name']) && trim($metadata['name']) === '') {
            throw new InvalidArgumentException('Fixed cost occurrence name cannot be empty.');
        }

        $occurrence = FixedCostOccurrence::query()
            ->where('user_id', $userId)
            ->findOrFail($occurrenceId);

        // Filter to only known-safe columns before persisting.
        $safeUpdates = array_intersect_key($metadata, array_flip($allowedFields));

        if (! empty($safeUpdates)) {
            $occurrence->update($safeUpdates);
        }
    }
}
