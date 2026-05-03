<?php

namespace App\Domains\FixedCost\DTOs;

final readonly class UpdateFixedCostOccurrenceMetadataData
{
    public function __construct(
        public ?string $name,
        public ?string $note,
    ) {}
}
