<?php

namespace App\Domains\User\Exceptions;

use Carbon\Carbon;
use RuntimeException;

class UserBannedException extends RuntimeException
{
    public function __construct(
        public readonly ?Carbon $bannedUntil = null,
    ) {
        parent::__construct('Your account has been banned.');
    }
}
