<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Http\Controllers\Controller;
use App\Domains\Transaction\Actions\CreateTransactionAction;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Http\Resources\Category\CategoryResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TransactionController extends Controller
{
    /**
     * Show the form for creating a new transaction.
     */
    public function create(
        GetAllSystemCategoriesAction $systemCategoriesAction,
        GetAllCustomCategoriesAction $customCategoriesAction
    ): Response {
        $user = Auth::user();

        $systemCategories = $systemCategoriesAction->execute();
        $customCategories = $customCategoriesAction->execute($user->id);
        $allCategories = $systemCategories->merge($customCategories);
        $resolvedCategories = CategoryResource::collection($allCategories)->resolve();

        return Inertia::render('Budgeting/Dashboard/Create', [
            'categories' => $resolvedCategories,
        ]);
    }

    /**
     * Store a newly created transaction in storage via Inertia.
     * @throws Throwable
     */
    public function store(
        StoreTransactionRequest $request,
        CreateTransactionAction $action
    ): RedirectResponse {
        $action->execute(Auth::user(), $request->toDTO());

        return redirect()->back()->with('success', 'Transaction created successfully.');
    }
}
