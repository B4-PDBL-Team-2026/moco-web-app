<?php

namespace App\Domains\Profile\DTOs;

class UpdateProfileData
{
    public function __construct(
        public readonly ?string $displayName,
        public readonly ?string $avatarUrl,
        public readonly ?string $currency,
        public readonly ?string $locale,
    ) {}

    public static function fromArray(array $validated): self
    {
        return new self(
            displayName: $validated['display_name'] ?? null,
            avatarUrl: $validated['avatar_url'] ?? null,
            currency: $validated['currency'] ?? null,
            locale: $validated['locale'] ?? null,
        );
    }
}