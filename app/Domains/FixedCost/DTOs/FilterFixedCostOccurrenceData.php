<?php

namespace App\Domains\FixedCost\DTOs;

use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;

final readonly class FilterFixedCostOccurrenceData
{
    public function __construct(
        public ?string $keyword,
        public ?FixedCostOccurenceStatus $status,
        public ?string $startDate,
        public ?string $endDate,
        public int $page,
        public int $perPage,
    ) {}
}
