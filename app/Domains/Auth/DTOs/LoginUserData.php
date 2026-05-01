<?php

namespace App\Domains\Auth\DTOs;

final readonly class LoginUserData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
