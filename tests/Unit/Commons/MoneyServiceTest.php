<?php

namespace Tests\Unit\Commons;

use PHPUnit\Framework\TestCase;
use App\Commons\MoneyService;

class MoneyServiceTest extends TestCase
{

    public function test_it_normalizes_value_to_two_decimal_string()
    {
        $this->assertEquals('100.00', MoneyService::normalize(100));
        $this->assertEquals('1500.50', MoneyService::normalize(1500.5));
    }

    public function test_it_adds_money_values_accurately()
    {

        $result = MoneyService::add('0.1', '0.2');
        $this->assertEquals('0.30', $result);
    }

    public function test_it_compares_financial_values_correctly()
    {

        $this->assertTrue(MoneyService::gt('1000.00', '500.00'));

        $this->assertTrue(MoneyService::lte('500.00', '1000.00'));
    }
}
