<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
uses(Tests\TestCase::class)->in('Unit');

use App\Domains\Transactions\Actions\GetAllTransactionAction;
use App\Domains\Transactions\DTOs\FilterTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;

it('returns paginated transactions only for authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    $otherCategory = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    Transaction::factory()->count(2)->create([
        'user_id' => $otherUser->id,
        'category_id' => $otherCategory->id,
    ]);

    $dto = new FilterTransactionData(
        month: null,
        year: null,
        search: null,
        categoryId: null,
        perPage: 10,
    );

    $action = app(GetAllTransactionAction::class);

    $result = $action->execute($user->id, $dto);

    expect($result->total())->toBe(3);
});

it('filters transactions by month', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_date' => '2026-03-10',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_date' => '2026-02-10',
    ]);

    $dto = new FilterTransactionData(
        month: 3,
        year: null,
        search: null,
        categoryId: null,
        perPage: 10,
    );

    $action = app(GetAllTransactionAction::class);

    $result = $action->execute($user->id, $dto);

    expect($result->total())->toBe(1);
});

it('filters transactions by year using transaction_date', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_date' => '2026-01-10',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_date' => '2025-01-10',
    ]);

    $dto = new FilterTransactionData(
        month: null,
        year: 2026,
        search: null,
        categoryId: null,
        perPage: 10,
    );

    $action = app(GetAllTransactionAction::class);

    $result = $action->execute($user->id, $dto);

    expect($result->total())->toBe(1);
});

it('filters transactions by search keyword', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Groceries',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Internet Bill',
    ]);

    $dto = new FilterTransactionData(
        month: null,
        year: null,
        search: 'Groc',
        categoryId: null,
        perPage: 10,
    );

    $action = app(GetAllTransactionAction::class);

    $result = $action->execute($user->id, $dto);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Groceries');
});

it('filters transactions by category id', function () {
    $user = User::factory()->create();

    $categoryA = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    $categoryB = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $categoryA->id,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $categoryB->id,
    ]);

    $dto = new FilterTransactionData(
        month: null,
        year: null,
        search: null,
        categoryId: $categoryA->id,
        perPage: 10,
    );

    $action = app(GetAllTransactionAction::class);

    $result = $action->execute($user->id, $dto);

    expect($result->total())->toBe(1);
    expect($result->items()[0]->category_id)->toBe($categoryA->id);
});

it('uses requested per page pagination value', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->count(15)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $dto = new FilterTransactionData(
        month: null,
        year: null,
        search: null,
        categoryId: null,
        perPage: 5,
    );

    $action = app(GetAllTransactionAction::class);

    $result = $action->execute($user->id, $dto);

    expect($result->perPage())->toBe(5)
        ->and(count($result->items()))->toBe(5);
});
