<?php

namespace App\Domains\Transaction\DTOs;

final readonly class FilterTransactionData
{
    public function __construct(
        public ?int $month,
        public ?int $year,
        public ?string $search,
        public ?int $categoryId,
        public int $perPage = 10,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            month: isset($data['month']) ? (int) $data['month'] : null,
            year: isset($data['year']) ? (int) $data['year'] : null,
            search: $data['search'] ?? null,
            categoryId: isset($data['categoryId']) ? (int) $data['categoryId'] : null,
            perPage: isset($data['perPage']) ? (int) $data['perPage'] : 10,
        );
    }
}
