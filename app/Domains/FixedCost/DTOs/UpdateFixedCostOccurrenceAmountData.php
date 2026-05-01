<?php

namespace App\Domains\FixedCost\DTOs;

/**
 * Carries the new amount for a specific fixed cost occurrence.
 * Used exclusively in the cancel → edit → re-confirm flow described
 * in business rule §17.
 */
final readonly class UpdateFixedCostOccurrenceAmountData
{
    public function __construct(
        public string $amount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (string) $data['amount'],
        );
    }
}
