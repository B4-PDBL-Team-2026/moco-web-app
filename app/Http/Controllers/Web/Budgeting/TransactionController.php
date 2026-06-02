<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Transaction\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Category\CategoryResource;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(
        GetAllSystemCategoriesAction $systemCategoriesAction,
        GetAllCustomCategoriesAction $customCategoriesAction,
    ): Response {
        $systemCategories = $systemCategoriesAction->execute();
        $customCategories = $customCategoriesAction->execute(Auth::id());

        $categories = CategoryResource::collection(
            $systemCategories->merge($customCategories)
        )->resolve();

        return Inertia::render('Transaction/TransactionHistory', [
            'categories' => $categories,
        ]);
    }

    public function show(Transaction $transaction): Response
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(404);
        }

        $transaction->load('category');

        $systemCategories = app(GetAllSystemCategoriesAction::class)->execute();
        $customCategories = app(GetAllCustomCategoriesAction::class)->execute(Auth::id());
        $categories = CategoryResource::collection(
            $systemCategories->merge($customCategories)
        )->resolve();

        return Inertia::render('Transaction/TransactionDetail', [
            'transaction' => [
                'id' => $transaction->id,
                'name' => $transaction->name,
                'amount' => (float) $transaction->amount,
                'type' => $transaction->type,
                'source' => $transaction->source,
                'note' => $transaction->note,
                'transactionAt' => $transaction->transaction_at?->toIso8601String(),
                'category' => $transaction->relationLoaded('category') && $transaction->category
                    ? [
                        'id' => $transaction->category->id,
                        'name' => $transaction->category->name,
                        'icon' => $transaction->category->icon,
                    ]
                    : null,
            ],
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        $systemCategories = app(GetAllSystemCategoriesAction::class)->execute();
        $customCategories = app(GetAllCustomCategoriesAction::class)->execute(Auth::id());

        $categories = CategoryResource::collection(
            $systemCategories->merge($customCategories)
        )->resolve();

        return Inertia::render('Transaction/TransactionCreate', [
            'categories' => $categories,
        ]);
    }

    public function edit(Transaction $transaction): Response
    {
        if ($transaction->user_id !== Auth::id()) {
            abort(404);
        }

        $transaction->load('category');

        $systemCategories = app(GetAllSystemCategoriesAction::class)->execute();
        $customCategories = app(GetAllCustomCategoriesAction::class)->execute(Auth::id());

        $categories = CategoryResource::collection(
            $systemCategories->merge($customCategories)
        )->resolve();

        return Inertia::render('Transaction/TransactionUpdate', [
            'transaction' => [
                'id' => $transaction->id,
                'name' => $transaction->name,
                'amount' => (float) $transaction->amount,
                'type' => $transaction->type,
                'source' => $transaction->source,
                'note' => $transaction->note,
                'transactionAt' => $transaction->transaction_at?->toIso8601String(),
                'categoryId' => $transaction->category_id,
            ],
            'categories' => $categories,
        ]);
    }
}
