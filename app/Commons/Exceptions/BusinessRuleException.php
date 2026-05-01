<?php

namespace App\Commons\Exceptions;

use RuntimeException;

class BusinessRuleException extends RuntimeException
{
    public function __construct(
        protected readonly string $translationKey,
        protected readonly array $translationParams = [],
        protected readonly int $httpStatus = 422,
    ) {
        logger()->info('bre called');
        parent::__construct($this->translationKey);
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function getTranslationParams(): array
    {
        return $this->translationParams;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
