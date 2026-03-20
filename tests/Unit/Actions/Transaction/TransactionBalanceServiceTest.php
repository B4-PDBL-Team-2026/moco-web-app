<?php

use App\Domains\Budgeting\Services\TransactionBalanceService;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Transaction;

it('adds amount to balance for income transaction', function () {
    $service = app(TransactionBalanceService::class);

    $result = $service->applyTransaction('1000.00', TransactionType::INCOME, '250.00');

    expect($result)->toBe('1250.00');
});

it('subtracts amount from balance for expense transaction', function () {
    $service = app(TransactionBalanceService::class);

    $result = $service->applyTransaction('1000.00', TransactionType::EXPENSE, '250.00');

    expect($result)->toBe('750.00');
});

it('reverses income transaction from balance', function () {
    $service = app(TransactionBalanceService::class);

    $result = $service->reverseTransaction('1000.00', TransactionType::INCOME, '250.00');

    expect($result)->toBe('750.00');
});

it('reverses expense transaction from balance', function () {
    $service = app(TransactionBalanceService::class);

    $result = $service->reverseTransaction('1000.00', TransactionType::EXPENSE, '250.00');

    expect($result)->toBe('1250.00');
});

it('reapplies updated transaction when old was expense and new is income', function () {
    $service = app(TransactionBalanceService::class);

    $transaction = Transaction::factory()->make([
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    $result = $service->reapplyUpdatedTransaction(
        currentBalance: '900.00',
        oldTransaction: $transaction,
        newType: TransactionType::INCOME,
        newAmount: '200.00',
    );

    expect($result)->toBe('1200.00');
});

it('reapplies updated transaction when only amount changes', function () {
    $service = app(TransactionBalanceService::class);

    $transaction = Transaction::factory()->make([
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    $result = $service->reapplyUpdatedTransaction(
        currentBalance: '900.00',
        oldTransaction: $transaction,
        newType: TransactionType::EXPENSE,
        newAmount: '250.00',
    );

    expect($result)->toBe('750.00');
});
