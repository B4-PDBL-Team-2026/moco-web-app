<?php

namespace App\Domains\Category\Actions;

use App\Models\Category;
use Cache;
use Illuminate\Support\Collection;

/**
 * Retrieves the list of standardized system categories.
 * Since system categories are static and infrequently updated,
 * this action implements a caching layer to reduce database load
 * and ensures a consistent alphabetical sorting for the UI.
 */
final readonly class GetSystemCategoriesAction
{
    /**
     * Cache key for system categories.
     */
    private const CACHE_KEY = 'system_categories_list';

    /**
     * Execute the action to fetch system categories.
     *
     * @return Collection<int, Category>
     */
    public function execute(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return Category::query()
                ->where('is_system', '=', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Helper to clear the cache if system categories are updated via seeder/migration.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
