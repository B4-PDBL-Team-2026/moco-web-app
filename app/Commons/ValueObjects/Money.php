<?php

namespace App\Commons\ValueObjects;

final class Money
{
    private const SCALE = 2;

    private const COMPARE_SCALE = 4;

    public static function normalize(string|int|float $value): string
    {
        $cleanValue = str_replace(',', '', (string) $value);

        return number_format((float) $cleanValue, self::SCALE, '.', '');
    }

    public static function add(string $left, string $right): string
    {
        return bcadd($left, $right, self::SCALE);
    }

    public static function sub(string $left, string $right): string
    {
        return bcsub($left, $right, self::SCALE);
    }

    public static function div(string $left, string $right): string
    {
        return bcdiv($left, $right, self::SCALE);
    }

    public static function mul(string $left, string $right): string
    {
        return bcmul($left, $right, self::SCALE);
    }

    public static function min(string $left, string $right): string
    {
        return self::lte($left, $right) ? $left : $right;
    }

    public static function max(string $left, string $right): string
    {
        return self::gte($left, $right) ? $left : $right;
    }

    public static function gt(string $left, string $right): bool
    {
        return bccomp($left, $right, self::COMPARE_SCALE) === 1;
    }

    public static function gte(string $left, string $right): bool
    {
        return bccomp($left, $right, self::COMPARE_SCALE) >= 0;
    }

    public static function lt(string $left, string $right): bool
    {
        return bccomp($left, $right, self::COMPARE_SCALE) === -1;
    }

    public static function lte(string $left, string $right): bool
    {
        return bccomp($left, $right, self::COMPARE_SCALE) <= 0;
    }

    public static function eq(string $left, string $right): bool
    {
        return bccomp($left, $right, self::COMPARE_SCALE) === 0;
    }
}
