<?php

use App\Domains\Transactions\Actions\GetAllTransactionAction;
use App\Domains\Transactions\DTOs\FilterTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

if (! function_exists('txFilters')) {
    function txFilters(array $overrides = []): FilterTransactionData
    {
        return new FilterTransactionData(
            month: $overrides['month'] ?? null,
            year: $overrides['year'] ?? null,
            search: $overrides['search'] ?? null,
            categoryId: $overrides['categoryId'] ?? null,
            categoryType: $overrides['categoryType'] ?? null,
            perPage: $overrides['perPage'] ?? 10,
        );
    }
}

if (! function_exists('makeTx')) {
    function makeTx(User $user, array $overrides = []): Transaction
    {
        $cat = SystemCategory::factory()->create();

        return Transaction::factory()->create(array_merge([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'category_type' => SystemCategory::class, // Nanti kalau morphMap udah jalan, ganti jadi 'system'
            'type' => TransactionType::EXPENSE->value,
            'transaction_date' => '2026-03-15',
        ], $overrides));
    }
}

beforeEach(function () {
    $this->action = app(GetAllTransactionAction::class);
    $this->user = User::factory()->create();
});

it('returns a LengthAwarePaginator', function () {
    $result = $this->action->execute($this->user->id, txFilters());

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('returns paginated transactions only for the given user', function () {
    makeTx($this->user);
    makeTx($this->user);
    makeTx($this->user);

    $other = User::factory()->create();
    makeTx($other);
    makeTx($other);

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->total())->toBe(3);
});

it('returns empty collection when user has no transactions', function () {
    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->total())->toBe(0);
});

it('filters transactions by month', function () {
    makeTx($this->user, ['transaction_date' => '2026-03-10']);
    makeTx($this->user, ['transaction_date' => '2026-02-10']);

    $result = $this->action->execute($this->user->id, txFilters(['month' => 3]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->transaction_date->format('m'))->toBe('03');
});

it('returns all transactions when month filter is null', function () {
    makeTx($this->user, ['transaction_date' => '2026-01-10']);
    makeTx($this->user, ['transaction_date' => '2026-06-10']);
    makeTx($this->user, ['transaction_date' => '2026-12-10']);

    $result = $this->action->execute($this->user->id, txFilters(['month' => null]));

    expect($result->total())->toBe(3);
});

it('returns empty when month filter matches no transactions', function () {
    makeTx($this->user, ['transaction_date' => '2026-03-10']);

    $result = $this->action->execute($this->user->id, txFilters(['month' => 12]));

    expect($result->total())->toBe(0);
});

it('filters transactions by year', function () {
    makeTx($this->user, ['transaction_date' => '2026-01-10']);
    makeTx($this->user, ['transaction_date' => '2025-01-10']);

    $result = $this->action->execute($this->user->id, txFilters(['year' => 2026]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->transaction_date->format('Y'))->toBe('2026');
});

it('returns all transactions when year filter is null', function () {
    makeTx($this->user, ['transaction_date' => '2024-01-10']);
    makeTx($this->user, ['transaction_date' => '2025-01-10']);
    makeTx($this->user, ['transaction_date' => '2026-01-10']);

    $result = $this->action->execute($this->user->id, txFilters(['year' => null]));

    expect($result->total())->toBe(3);
});

it('returns empty when year filter matches no transactions', function () {
    makeTx($this->user, ['transaction_date' => '2026-03-10']);

    $result = $this->action->execute($this->user->id, txFilters(['year' => 2020]));

    expect($result->total())->toBe(0);
});

it('filters by both month and year together', function () {
    makeTx($this->user, ['transaction_date' => '2026-03-10']); // match
    makeTx($this->user, ['transaction_date' => '2025-03-10']); // wrong year
    makeTx($this->user, ['transaction_date' => '2026-04-10']); // wrong month

    $result = $this->action->execute($this->user->id, txFilters(['month' => 3, 'year' => 2026]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->transaction_date->format('Y-m'))->toBe('2026-03');
});

it('filters transactions by partial name search', function () {
    makeTx($this->user, ['name' => 'Groceries']);
    makeTx($this->user, ['name' => 'Internet Bill']);

    $result = $this->action->execute($this->user->id, txFilters(['search' => 'Groc']));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Groceries');
});

it('search matches the keyword anywhere in the name', function () {
    makeTx($this->user, ['name' => 'Monthly Netflix Subscription']);
    makeTx($this->user, ['name' => 'Netflix Basic']);
    makeTx($this->user, ['name' => 'Spotify']);

    $result = $this->action->execute($this->user->id, txFilters(['search' => 'Netflix']));

    expect($result->total())->toBe(2);
});

it('returns all transactions when search is null', function () {
    makeTx($this->user, ['name' => 'Groceries']);
    makeTx($this->user, ['name' => 'Internet Bill']);

    $result = $this->action->execute($this->user->id, txFilters(['search' => null]));

    expect($result->total())->toBe(2);
});

it('returns empty when search matches nothing', function () {
    makeTx($this->user, ['name' => 'Groceries']);

    $result = $this->action->execute($this->user->id, txFilters(['search' => 'zzznomatch']));

    expect($result->total())->toBe(0);
});

it('filters transactions by system category id and type', function () {
    $catA = SystemCategory::factory()->create();
    $catB = SystemCategory::factory()->create();

    makeTx($this->user, ['category_id' => $catA->id, 'category_type' => SystemCategory::class]);
    makeTx($this->user, ['category_id' => $catB->id, 'category_type' => SystemCategory::class]);

    $result = $this->action->execute($this->user->id, txFilters([
        'categoryId' => $catA->id,
        'categoryType' => SystemCategory::class,
    ]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->category_id)->toBe($catA->id)
        ->and($result->items()[0]->category_type)->toBe(SystemCategory::class);
});

it('filters transactions by custom category id and type', function () {
    $custom = CustomCategory::factory()->create(['user_id' => $this->user->id]);
    $sys = SystemCategory::factory()->create();

    makeTx($this->user, ['category_id' => $custom->id, 'category_type' => CustomCategory::class]);
    makeTx($this->user, ['category_id' => $sys->id,    'category_type' => SystemCategory::class]);

    $result = $this->action->execute($this->user->id, txFilters([
        'categoryId' => $custom->id,
        'categoryType' => CustomCategory::class,
    ]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->category_id)->toBe($custom->id)
        ->and($result->items()[0]->category_type)->toBe(CustomCategory::class);
});

it('scopes by categoryType so a system and custom category sharing the same id do not mix', function () {
    // Both tables are independent, so a system_category and a custom_category
    // could have the same numeric id. categoryType must scope the query correctly.
    $sys = SystemCategory::factory()->create();
    $custom = CustomCategory::factory()->create(['user_id' => $this->user->id]);

    makeTx($this->user, ['category_id' => $sys->id,    'category_type' => SystemCategory::class]);
    makeTx($this->user, ['category_id' => $custom->id, 'category_type' => CustomCategory::class]);

    $sysResult = $this->action->execute($this->user->id, txFilters([
        'categoryId' => $sys->id,
        'categoryType' => SystemCategory::class,
    ]));

    expect($sysResult->total())->toBe(1)
        ->and($sysResult->items()[0]->category_type)->toBe(SystemCategory::class);
});

it('returns all transactions when categoryId is null', function () {
    $catA = SystemCategory::factory()->create();
    $catB = SystemCategory::factory()->create();

    makeTx($this->user, ['category_id' => $catA->id]);
    makeTx($this->user, ['category_id' => $catB->id]);

    $result = $this->action->execute($this->user->id, txFilters(['categoryId' => null]));

    expect($result->total())->toBe(2);
});

it('respects the perPage value', function () {
    for ($i = 0; $i < 15; $i++) {
        makeTx($this->user);
    }

    $result = $this->action->execute($this->user->id, txFilters(['perPage' => 5]));

    expect($result->perPage())->toBe(5)
        ->and($result->items())->toHaveCount(5)
        ->and($result->total())->toBe(15);
});

it('returns correct total and last page across pages', function () {
    for ($i = 0; $i < 20; $i++) {
        makeTx($this->user);
    }

    $result = $this->action->execute($this->user->id, txFilters(['perPage' => 5]));

    expect($result->total())->toBe(20)
        ->and($result->lastPage())->toBe(4);
});

it('defaults to 10 items per page', function () {
    for ($i = 0; $i < 15; $i++) {
        makeTx($this->user);
    }

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->perPage())->toBe(10)
        ->and($result->items())->toHaveCount(10);
});

it('returns transactions in latest-first order', function () {
    $first = makeTx($this->user, ['created_at' => now()->subMinutes(3)]);
    $second = makeTx($this->user, ['created_at' => now()->subMinutes(2)]);
    $third = makeTx($this->user, ['created_at' => now()->subMinutes(1)]);

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->items()[0]->id)->toBe($third->id)
        ->and($result->items()[2]->id)->toBe($first->id);
});

it('excludes soft-deleted transactions', function () {
    makeTx($this->user, ['name' => 'Active']);
    $deleted = makeTx($this->user, ['name' => 'Deleted']);
    $deleted->delete();

    $result = $this->action->execute($this->user->id, txFilters());

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Active');
});
