<?php

namespace App\Domains\Auth\DTOs;

final readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
