<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

// Cukup gunakan satu blok ini saja untuk semua folder di bawah 'Feature'
uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class
)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations ... (sisanya biarkan saja)
*/
