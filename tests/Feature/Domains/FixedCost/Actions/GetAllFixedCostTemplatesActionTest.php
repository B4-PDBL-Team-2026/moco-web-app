<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\GetAllFixedCostTemplatesAction;
use App\Domains\FixedCost\DTOs\FilterFixedCostTemplateData;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

function filters(array $overrides = []): FilterFixedCostTemplateData
{
    return FilterFixedCostTemplateData::fromArray(array_merge([
        'page' => 1,
    ], $overrides));
}

function setupTemplate(User $user, array $overrides = []): FixedCostTemplate
{
    $cat = Category::factory()->expense()->create();

    return FixedCostTemplate::factory()->create(array_merge([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 15,
        'is_active' => true,

        'category_id' => $cat->id,
    ], $overrides));
}

beforeEach(function () {
    $this->action = new GetAllFixedCostTemplatesAction;
    $this->user = User::factory()->create();
});

it('returns a LengthAwarePaginator instance', function () {
    $result = $this->action->execute($this->user->id, filters());

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('returns an empty paginator when user has no templates', function () {
    $result = $this->action->execute($this->user->id, filters());

    expect($result->total())->toBe(0)
        ->and($result->items())->toBeEmpty();
});

it('only returns templates belonging to the given user', function () {
    setupTemplate($this->user, ['name' => 'Mine']);

    $other = User::factory()->create();
    setupTemplate($other, ['name' => 'Not Mine']);

    $result = $this->action->execute($this->user->id, filters());

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Mine');
});

it('excludes soft-deleted templates', function () {
    $t = setupTemplate($this->user, ['name' => 'Deleted']);
    $t->delete();

    setupTemplate($this->user, ['name' => 'Active']);

    $result = $this->action->execute($this->user->id, filters());

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Active');
});

it('returns templates ordered by name ascending', function () {
    setupTemplate($this->user, ['name' => 'Spotify']);
    setupTemplate($this->user, ['name' => 'Netflix']);
    setupTemplate($this->user, ['name' => 'Amazon Prime']);

    $result = $this->action->execute($this->user->id, filters());

    $names = collect($result->items())->pluck('name')->toArray();
    expect($names)->toBe(['Amazon Prime', 'Netflix', 'Spotify']);
});

it('uses id as a stable tie-breaker when names collide', function () {
    $first = setupTemplate($this->user, ['name' => 'Duplicate']);
    $second = setupTemplate($this->user, ['name' => 'Duplicate']);

    $result = $this->action->execute($this->user->id, filters());

    $ids = collect($result->items())->pluck('id')->toArray();
    expect($ids)->toBe([$first->id, $second->id]);
});

it('filters by keyword with a partial name match', function () {
    setupTemplate($this->user, ['name' => 'Netflix']);
    setupTemplate($this->user, ['name' => 'Spotify']);
    setupTemplate($this->user, ['name' => 'Amazon Prime']);

    $result = $this->action->execute($this->user->id, filters(['keyword' => 'net']));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Netflix');
});

it('keyword filter is case-insensitive on MySQL/SQLite', function () {
    setupTemplate($this->user, ['name' => 'Netflix']);

    $result = $this->action->execute($this->user->id, filters(['keyword' => 'NETFLIX']));

    expect($result->total())->toBe(1);
});

it('keyword filter matches templates containing the keyword anywhere in the name', function () {
    setupTemplate($this->user, ['name' => 'My Netflix Subscription']);
    setupTemplate($this->user, ['name' => 'Netflix Basic']);
    setupTemplate($this->user, ['name' => 'Spotify']);

    $result = $this->action->execute($this->user->id, filters(['keyword' => 'Netflix']));

    expect($result->total())->toBe(1);
});

it('returns empty result when keyword matches nothing', function () {
    setupTemplate($this->user, ['name' => 'Netflix']);

    $result = $this->action->execute($this->user->id, filters(['keyword' => 'zzznomatch']));

    expect($result->total())->toBe(0);
});

it('ignores keyword filter when keyword is null', function () {
    setupTemplate($this->user, ['name' => 'Netflix']);
    setupTemplate($this->user, ['name' => 'Spotify']);

    $result = $this->action->execute($this->user->id, filters(['keyword' => null]));

    expect($result->total())->toBe(2);
});

it('ignores keyword filter when keyword is an empty string', function () {
    setupTemplate($this->user, ['name' => 'Netflix']);
    setupTemplate($this->user, ['name' => 'Spotify']);

    $result = $this->action->execute($this->user->id, filters(['keyword' => '  ']));

    expect($result->total())->toBe(2);
});

it('filters by exact due_day', function () {
    setupTemplate($this->user, ['due_day' => 10, 'name' => 'A']);
    setupTemplate($this->user, ['due_day' => 15, 'name' => 'B']);
    setupTemplate($this->user, ['due_day' => 15, 'name' => 'C']);

    $result = $this->action->execute($this->user->id, filters(['dueDay' => 15]));

    expect($result->total())->toBe(2);
    collect($result->items())->each(fn ($t) => expect($t->due_day)->toBe(15));
});

it('returns empty when no template has the given due_day', function () {
    setupTemplate($this->user, ['due_day' => 10]);

    $result = $this->action->execute($this->user->id, filters(['dueDay' => 31]));

    expect($result->total())->toBe(0);
});

it('ignores due_day filter when dueDay is null', function () {
    setupTemplate($this->user, ['due_day' => 5]);
    setupTemplate($this->user, ['due_day' => 20]);

    $result = $this->action->execute($this->user->id, filters(['dueDay' => null]));

    expect($result->total())->toBe(2);
});

it('filters by cycle_type monthly', function () {
    setupTemplate($this->user, ['cycle_type' => 'monthly', 'name' => 'Monthly']);
    setupTemplate($this->user, ['cycle_type' => 'weekly',  'name' => 'Weekly', 'due_day' => 3]);

    $result = $this->action->execute($this->user->id, filters(['cycleType' => 'monthly']));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Monthly');
});

it('filters by cycle_type weekly', function () {
    setupTemplate($this->user, ['cycle_type' => 'monthly', 'name' => 'Monthly']);
    setupTemplate($this->user, ['cycle_type' => 'weekly',  'name' => 'Weekly A', 'due_day' => 1]);
    setupTemplate($this->user, ['cycle_type' => 'weekly',  'name' => 'Weekly B', 'due_day' => 3]);

    $result = $this->action->execute($this->user->id, filters(['cycleType' => 'weekly']));

    expect($result->total())->toBe(2);
});

it('ignores cycleType filter when cycleType is null', function () {
    setupTemplate($this->user, ['cycle_type' => 'monthly']);
    setupTemplate($this->user, ['cycle_type' => 'weekly', 'due_day' => 2]);

    $result = $this->action->execute($this->user->id, filters(['cycleType' => null]));

    expect($result->total())->toBe(2);
});

it('filters by isActive true', function () {
    setupTemplate($this->user, ['is_active' => true,  'name' => 'Active']);
    setupTemplate($this->user, ['is_active' => false, 'name' => 'Inactive']);

    $result = $this->action->execute($this->user->id, filters(['isActive' => true]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Active');
});

it('filters by isActive false', function () {
    setupTemplate($this->user, ['is_active' => true,  'name' => 'Active']);
    setupTemplate($this->user, ['is_active' => false, 'name' => 'Inactive']);

    $result = $this->action->execute($this->user->id, filters(['isActive' => false]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Inactive');
});

it('returns both active and inactive when isActive filter is null', function () {
    setupTemplate($this->user, ['is_active' => true]);
    setupTemplate($this->user, ['is_active' => false]);

    $result = $this->action->execute($this->user->id, filters(['isActive' => null]));

    expect($result->total())->toBe(2);
});

it('applies multiple filters simultaneously', function () {
    setupTemplate($this->user, ['name' => 'Netflix', 'cycle_type' => 'monthly', 'due_day' => 15, 'is_active' => true]);
    setupTemplate($this->user, ['name' => 'Netflix Paused', 'cycle_type' => 'monthly', 'due_day' => 15, 'is_active' => false]);
    setupTemplate($this->user, ['name' => 'Spotify', 'cycle_type' => 'monthly', 'due_day' => 10, 'is_active' => true]);

    $result = $this->action->execute($this->user->id, filters([
        'keyword' => 'Netflix',
        'cycleType' => 'monthly',
        'dueDay' => 15,
        'isActive' => true,
    ]));

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->name)->toBe('Netflix');
});

it('returns empty when combined filters have no matching records', function () {
    setupTemplate($this->user, ['name' => 'Netflix', 'cycle_type' => 'monthly', 'due_day' => 15]);

    $result = $this->action->execute($this->user->id, filters([
        'keyword' => 'Netflix',
        'cycleType' => 'weekly',  // conflicts — Netflix is monthly
    ]));

    expect($result->total())->toBe(0);
});

it('paginates results correctly', function () {
    foreach (range(1, 5) as $i) {
        setupTemplate($this->user, ['name' => "Template {$i}"]); // alphabetical: Template 1…5
    }

    $page1 = $this->action->execute($this->user->id, filters(['perPage' => 2, 'page' => 1]));
    $page2 = $this->action->execute($this->user->id, filters(['perPage' => 2, 'page' => 2]));
    $page3 = $this->action->execute($this->user->id, filters(['perPage' => 2, 'page' => 3]));

    expect($page1->total())->toBe(5)
        ->and($page1->items())->toHaveCount(2)
        ->and($page2->items())->toHaveCount(2)
        ->and($page3->items())->toHaveCount(1);
});

it('returns correct total count across all pages', function () {
    foreach (range(1, 20) as $i) {
        setupTemplate($this->user, ['name' => "T{$i}"]);
    }

    $result = $this->action->execute($this->user->id, filters(['perPage' => 5, 'page' => 1]));

    expect($result->total())->toBe(20)
        ->and($result->lastPage())->toBe(4);
});

it('returns an empty last page gracefully when page exceeds total', function () {
    setupTemplate($this->user);

    $result = $this->action->execute($this->user->id, filters(['perPage' => 15, 'page' => 99]));

    expect($result->items())->toBeEmpty()
        ->and($result->total())->toBe(1);
});

it('defaults to 15 items per page when perPage is not provided', function () {
    foreach (range(1, 20) as $i) {
        setupTemplate($this->user, ['name' => "T{$i}"]);
    }

    $result = $this->action->execute($this->user->id, filters());

    expect($result->perPage())->toBe(FilterFixedCostTemplateData::DEFAULT_PER_PAGE)
        ->and($result->items())->toHaveCount(10);
});

it('clamps perPage to MAX_PER_PAGE', function () {
    $dto = FilterFixedCostTemplateData::fromArray(['perPage' => 9999]);

    expect($dto->perPage)->toBe(FilterFixedCostTemplateData::MAX_PER_PAGE);
});
