<?php

namespace App\Http\Controllers\Web\Category;

use App\Domains\Category\Actions\CreateCustomCategoryAction;
use App\Domains\Category\Actions\DeleteCustomCategoryAction;
use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Category\Actions\UpdateCustomCategoryAction;
use App\Domains\Category\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCustomCategoryRequest;
use App\Http\Requests\Category\UpdateCustomCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CategoryController extends Controller
{
    public function index(
        GetAllSystemCategoriesAction $systemAction,
        GetAllCustomCategoriesAction $customAction
    ): Response {
        $userId = auth()->id();
        $systemCategories = $systemAction->execute();
        $customCategories = $customAction->execute($userId);

        $transactionCounts = \DB::table('transactions')
            ->whereNull('deleted_at')
            ->where('user_id', $userId)
            ->groupBy('category_id')
            ->select('category_id', \DB::raw('count(*) as aggregate'))
            ->pluck('aggregate', 'category_id');

        $fixedCostCounts = \DB::table('fixed_cost_templates')
            ->whereNull('deleted_at')
            ->where('user_id', $userId)
            ->groupBy('category_id')
            ->select('category_id', \DB::raw('count(*) as aggregate'))
            ->pluck('aggregate', 'category_id');

        $snapshot = auth()->user()->budgetSnapshot;

        return Inertia::render('Category/CategoryManagement', [
            'systemCategories' => $systemCategories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'type' => $c->type instanceof \BackedEnum ? $c->type->value : $c->type,
                'isSystem' => true,
                'transactionsCount' => $transactionCounts->get($c->id, 0),
                'fixedCostsCount' => $fixedCostCounts->get($c->id, 0),
            ]),
            'customCategories' => $customCategories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'icon' => $c->icon,
                'type' => $c->type instanceof \BackedEnum ? $c->type->value : $c->type,
                'isSystem' => false,
                'transactionsCount' => $transactionCounts->get($c->id, 0),
                'fixedCostsCount' => $fixedCostCounts->get($c->id, 0),
            ]),
            'status' => $snapshot?->financial_condition,
        ]);
    }

    /**
     * Store a new custom category.
     *
     * @throws Throwable
     */
    public function store(
        StoreCustomCategoryRequest $request,
        CreateCustomCategoryAction $action
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            data: $request->toDTO()
        );

        return back()->with('success', 'Kategori kustom berhasil ditambahkan.');
    }

    /**
     * Update an existing custom category.
     *
     * @throws Throwable
     */
    public function update(
        UpdateCustomCategoryRequest $request,
        int $categoryId,
        UpdateCustomCategoryAction $action
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            categoryId: $categoryId,
            data: $request->toDTO()
        );

        return back()->with('success', 'Kategori kustom berhasil diperbarui.');
    }

    /**
     * Delete a custom category.
     *
     * @throws Throwable
     */
    public function destroy(
        Request $request,
        int $categoryId,
        DeleteCustomCategoryAction $action
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            categoryId: $categoryId
        );

        return back()->with('success', 'Kategori kustom berhasil dihapus.');
    }
}
