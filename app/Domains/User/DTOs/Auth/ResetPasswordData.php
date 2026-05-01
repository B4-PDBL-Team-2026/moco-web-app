<?php

namespace App\Domains\User\DTOs\Auth;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $token,
    ) {}
}
