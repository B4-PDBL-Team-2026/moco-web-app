<?php

use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\DTOs\FilterFixedCostTemplateData;
use App\Domains\User\Models\User;
use Laravel\Sanctum\Sanctum;

test('unauthenticated request returns 401', function () {
    $this->getJson('/api/fixed-costs')->assertUnauthorized();
});

test('returns 200 with paginated structure inside api wrapper', function () {
    [$user, $cat] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs')->assertOk();

    // Sesuai dengan struktur ApiResponse resolve() method
    $response->assertJsonStructure([
        'success',
        'message',
        'data' => [
            '*' => ['id', 'name', 'amount'], // asumsikan resource punya field ini
        ],
        'meta' => [
            'currentPage',
            'lastPage',
            'perPage',
            'total',
            'hasMore',
        ],
    ]);
});

test('returns empty data when user has no templates', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('data', []);
});

test('returns templates belonging to the authenticated user only', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['name' => 'Mine']);

    $other = User::factory()->create();
    $otherCat = Category::factory()->expense()->create();
    createTemplate($other, $otherCat, ['name' => 'Not Mine']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Mine');
});

test('excludes soft-deleted templates', function () {
    [$user, $cat] = indexTemplateSetup();
    $t = createTemplate($user, $cat, ['name' => 'Deleted']);
    $t->delete();
    createTemplate($user, $cat, ['name' => 'Active']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Active');
});

test('filters templates by keyword partial match', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['name' => 'Netflix']);
    createTemplate($user, $cat, ['name' => 'Spotify']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?keyword=net')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Netflix');
});

test('keyword returns empty when nothing matches', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['name' => 'Netflix']);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?keyword=zzznomatch')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

test('omitting keyword returns all templates', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['name' => 'Netflix']);
    createTemplate($user, $cat, ['name' => 'Spotify']);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

test('filters by exact dueDay', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['name' => 'A', 'due_day' => 10]);
    createTemplate($user, $cat, ['name' => 'B', 'due_day' => 15]);
    createTemplate($user, $cat, ['name' => 'C', 'due_day' => 15]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?dueDay=15')->assertOk();

    expect($response->json('meta.total'))->toBe(2);
});

test('dueDay filter returns empty when no match', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['due_day' => 10]);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?dueDay=31')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

test('filters by cycleType monthly', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['cycle_type' => 'monthly', 'name' => 'Monthly']);
    createTemplate($user, $cat, ['cycle_type' => 'weekly',  'name' => 'Weekly', 'due_day' => 3]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?cycleType=monthly')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Monthly');
});

test('filters by cycleType weekly', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['cycle_type' => 'monthly', 'name' => 'Monthly']);
    createTemplate($user, $cat, ['cycle_type' => 'weekly',  'name' => 'WeeklyA', 'due_day' => 1]);
    createTemplate($user, $cat, ['cycle_type' => 'weekly',  'name' => 'WeeklyB', 'due_day' => 5]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?cycleType=weekly')->assertOk();

    expect($response->json('meta.total'))->toBe(2);
});

test('filters by isActive true returns only active templates', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['is_active' => true,  'name' => 'Active']);
    createTemplate($user, $cat, ['is_active' => false, 'name' => 'Inactive']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?isActive=1')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Active');
});

test('filters by isActive false returns only inactive templates', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['is_active' => true,  'name' => 'Active']);
    createTemplate($user, $cat, ['is_active' => false, 'name' => 'Inactive']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?isActive=0')->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Inactive');
});

test('omitting isActive returns both active and inactive', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['is_active' => true]);
    createTemplate($user, $cat, ['is_active' => false]);

    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

test('applies multiple filters simultaneously', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat, ['name' => 'Netflix', 'cycle_type' => 'monthly', 'due_day' => 15, 'is_active' => true]);
    createTemplate($user, $cat, ['name' => 'Netflix Paused', 'cycle_type' => 'monthly', 'due_day' => 15, 'is_active' => false]);
    createTemplate($user, $cat, ['name' => 'Spotify', 'cycle_type' => 'monthly', 'due_day' => 10]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?keyword=Netflix&cycleType=monthly&dueDay=15&isActive=1')
        ->assertOk();

    expect($response->json('meta.total'))->toBe(1)
        ->and($response->json('data.0.name'))->toBe('Netflix');
});

test('respects perPage parameter', function () {
    [$user, $cat] = indexTemplateSetup();
    foreach (range(1, 10) as $i) {
        createTemplate($user, $cat, ['name' => "Template {$i}"]);
    }

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?perPage=3')->assertOk();

    expect($response->json('meta.perPage'))->toBe(3)
        ->and(count($response->json('data')))->toBe(3)
        ->and($response->json('meta.total'))->toBe(10)
        ->and($response->json('meta.lastPage'))->toBe(4);
});

test('respects page parameter', function () {
    [$user, $cat] = indexTemplateSetup();
    foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
        createTemplate($user, $cat, ['name' => $letter]);
    }

    Sanctum::actingAs($user);

    $page1 = $this->getJson('/api/fixed-costs?perPage=2&page=1')->assertOk();
    $page2 = $this->getJson('/api/fixed-costs?perPage=2&page=2')->assertOk();

    expect($page1->json('data.0.name'))->toBe('A')
        ->and($page1->json('data.1.name'))->toBe('B');

    expect($page2->json('data.0.name'))->toBe('C')
        ->and($page2->json('data.1.name'))->toBe('D');
});

test('defaults to page 1 and perPage 10 when not provided', function () {
    [$user, $cat] = indexTemplateSetup();
    foreach (range(1, 20) as $i) {
        createTemplate($user, $cat, ['name' => "T{$i}"]);
    }

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs')->assertOk();

    expect($response->json('meta.currentPage'))->toBe(1)
        ->and($response->json('meta.perPage'))->toBe(FilterFixedCostTemplateData::DEFAULT_PER_PAGE)
        ->and(count($response->json('data')))->toBe(10);
});

test('returns empty data array for out-of-range page', function () {
    [$user, $cat] = indexTemplateSetup();
    createTemplate($user, $cat);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/fixed-costs?page=999')->assertOk();

    expect($response->json('data'))->toBeEmpty()
        ->and($response->json('meta.total'))->toBe(1);
});

test('returns 422 when cycleType is an invalid value', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?cycleType=quarterly')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cycleType']);
});

test('returns 422 when dueDay is 0', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?dueDay=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dueDay']);
});

test('returns 422 when dueDay exceeds 31', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?dueDay=32')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dueDay']);
});

test('returns 422 when perPage exceeds max', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?perPage='.(FilterFixedCostTemplateData::MAX_PER_PAGE + 1))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['perPage']);
});

test('returns 422 when page is 0', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);
});

test('accepts null values for nullable filter params', function () {
    [$user] = indexTemplateSetup();
    Sanctum::actingAs($user);

    $this->getJson('/api/fixed-costs?keyword=&cycleType=&isActive=')
        ->assertOk();
});
