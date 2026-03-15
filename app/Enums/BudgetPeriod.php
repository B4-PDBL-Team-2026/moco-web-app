<?php
namespace App\Enums;

enum SiklusUang: string
{
    case MINGGUAN = 'mingguan';
    case BULANAN = 'bulanan';

    public function jumlahHari(): int
    {
        return match($this) {
            self::MINGGUAN => 7,
            self::BULANAN => 30,
        };
    }
}
