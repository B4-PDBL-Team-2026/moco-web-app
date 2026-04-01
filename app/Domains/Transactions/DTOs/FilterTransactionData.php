<?php

namespace App\Domains\Transactions\DTOs;

use App\Models\CustomCategory;
use App\Models\SystemCategory;

final readonly class FilterTransactionData
{
    public function __construct(
        public ?int $month,
        public ?int $year,
        public ?string $search,
        public ?int $categoryId,
        public ?string $categoryType,
        public int $perPage = 10,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            month: isset($data['month']) ? (int) $data['month'] : null,
            year: isset($data['year']) ? (int) $data['year'] : null,
            search: $data['search'] ?? null,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            categoryType: self::resolveCategoryType($data['categoryType'] ?? null),
            perPage: isset($data['perPage']) ? (int) $data['perPage'] : 10,
        );
    }

    private static function resolveCategoryType(?string $type): ?string
    {
        return match ($type) {
            'system' => SystemCategory::class,
            'custom' => CustomCategory::class,
            default => null,
        };
    }
}
