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

    public static function fromArray(array $data): self
    {
        $keyword = isset($data['keyword']) ? trim($data['keyword']) : null;
        $perPage = min((int) ($data['perPage'] ?? self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);
        $page = max(1, (int) ($data['page'] ?? 1));
        $dueDay = isset($data['dueDay']) ? (int) $data['dueDay'] : null;
        $cycleType = isset($data['cycleType']) ? CycleType::from($data['cycleType']) : null;
        $isActive = isset($data['isActive']) ? filter_var($data['isActive'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;

        return new self(
            keyword: ($keyword !== null && $keyword !== '') ? $keyword : null,
            dueDay: $dueDay,
            cycleType: $cycleType,
            isActive: $isActive,
            perPage: $perPage > 0 ? $perPage : self::DEFAULT_PER_PAGE,
            page: $page,
        );
    }
}
