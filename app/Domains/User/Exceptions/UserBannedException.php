<?php

namespace App\Domains\User\Exceptions;

use Carbon\CarbonInterface;
use RuntimeException;

class UserBannedException extends RuntimeException
{
    public ?CarbonInterface $bannedUntilAttr;

    public function __construct(
        public readonly ?CarbonInterface $bannedUntil = null,
    ) {
        $this->bannedUntilAttr = $bannedUntil;

        $message = 'Akun kamu telah ditangguhkan.';
        if ($bannedUntil) {
            $message .= ' Sampai dengan: '.$bannedUntil->translatedFormat('d M Y H:i');
        }

        parent::__construct($message);
    }
}
