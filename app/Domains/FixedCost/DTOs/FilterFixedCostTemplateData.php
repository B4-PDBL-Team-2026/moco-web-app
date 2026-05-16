<?php

namespace App\Domains\FixedCost\DTOs;

use App\Domains\Budgeting\Enums\CycleType;

/**
 * Carries filter and pagination parameters for listing fixed cost templates.
 *
 * All filter fields are optional — omitting them returns unfiltered results.
 * Defaults are enforced here so the action never has to guess.
 */
final class FilterFixedCostTemplateData
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 100;

    public function __construct(
        public readonly ?string $keyword,
        public readonly ?int $dueDay,
        public readonly ?CycleType $cycleType,
        public readonly ?bool $isActive,
        public readonly int $perPage,
        public readonly int $page,
    ) {}
}
