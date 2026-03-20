<?php

namespace App\Commons;

final class MoneyService
{
    public static function add(string $left, string $right, int $scale = 2): string
    {
        return bcadd($left, $right, $scale);
    }

    public static function sub(string $left, string $right, int $scale = 2): string
    {
        return bcsub($left, $right, $scale);
    }

    public static function div(string $left, string $right, int $scale = 2): string
    {
        return bcdiv($left, $right, $scale);
    }

    public static function compare(string $left, string $right, int $scale = 2): int
    {
        return bccomp($left, $right, $scale);
    }

    public static function min(string $left, string $right, int $scale = 2): string
    {
        return self::compare($left, $right, $scale) <= 0 ? $left : $right;
    }
}
