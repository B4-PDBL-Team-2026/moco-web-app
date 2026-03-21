<?php

use App\Domains\Transactions\Actions\GetTransactionDetailAction;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;

it('returns transaction by id for owner', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $action = app(GetTransactionDetailAction::class);

    $result = $action->execute($user, $transaction);

    expect($result->id)->toBe($transaction->id);
});

it('fails when transaction does not belong to user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
    ]);

    $action = app(GetTransactionDetailAction::class);

    $action->execute($user, $transaction);
})->throws(\Illuminate\Validation\UnauthorizedException::class);
