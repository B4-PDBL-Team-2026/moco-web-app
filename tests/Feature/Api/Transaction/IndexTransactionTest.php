<?php

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('guest cannot access transaction index endpoint', function () {
    $this->getJson('/api/transaction/transactions')
        ->assertUnauthorized();
});

test('authenticated user can get paginated transactions', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->getJson('/api/transaction/transactions')->dump();

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Transactions retrieved successfully.')
        ->assertJsonStructure([
            'success',
            'data' => [
                'current_page',
                'data',
                'per_page',
                'total',
            ],
            'message',
        ]);
});

test('authenticated user only sees their own transactions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $otherCategory = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->count(2)->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    Transaction::factory()->count(3)->create([
        'user_id' => $otherUser->id,
        'category_id' => $otherCategory->id,
    ]);

    $response = $this->getJson('/api/transaction/transactions');

    $response
        ->assertOk()
        ->assertJsonCount(2, 'data.data');
});

test('index endpoint validates month', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/transaction/transactions?month=13')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month'], 'data');
});

test('index endpoint validates year', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/transaction/transactions?year=26')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['year'], 'data');
});

test('index endpoint validates category id belongs to user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($user);

    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $this->getJson('/api/transaction/transactions?categoryId='.$category->id)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['categoryId'], 'data');
});

test('index endpoint validates per page', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/transaction/transactions?perPage=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['perPage'], 'data');
});

test('index endpoint can filter by month year search and category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $categoryA = Category::factory()->create([
        'name' => 'category A',
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $categoryB = Category::factory()->create([
        'name' => 'category B',
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $categoryA->id,
        'name' => 'Groceries March',
        'transaction_date' => '2026-03-10',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $categoryB->id,
        'name' => 'Internet Bill',
        'transaction_date' => '2026-02-10',
    ]);

    $response = $this->getJson(
        '/api/transaction/transactions?month=3&year=2026&search=Groceries&categoryId='.$categoryA->id
    );

    $response
        ->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.name', 'Groceries March');
});
