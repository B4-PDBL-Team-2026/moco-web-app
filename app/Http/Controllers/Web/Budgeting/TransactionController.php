<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Http\Controllers\Controller;
use App\Domains\Transaction\Actions\CreateTransactionAction;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Http\Resources\Category\CategoryResource;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Actions\GetTransactionDetailAction;
use App\Http\Resources\Transaction\TransactionResource;
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

    /**
     * Show the transaction history page.
     */
    public function history(
        GetAllSystemCategoriesAction $systemCategoriesAction,
        GetAllCustomCategoriesAction $customCategoriesAction
    ): Response {
        $user = Auth::user();

        $systemCategories = $systemCategoriesAction->execute();
        $customCategories = $customCategoriesAction->execute($user->id);
        $allCategories = $systemCategories->merge($customCategories);
        $resolvedCategories = CategoryResource::collection($allCategories)->resolve();

        return Inertia::render('Transaction/TransactionHistory', [
            'categories' => $resolvedCategories,
        ]);
    }

    /**
     * Show the transaction detail page.
     */
    public function show(
        Transaction $transaction,
        GetTransactionDetailAction $action
    ): Response {
        $user = Auth::user();
        $resolvedTransaction = $action->execute($user, $transaction);

        return Inertia::render('Transaction/TransactionDetail', [
            'transaction' => TransactionResource::make($resolvedTransaction)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(
        Transaction $transaction,
        GetTransactionDetailAction $detailAction,
        GetAllSystemCategoriesAction $systemCategoriesAction,
        GetAllCustomCategoriesAction $customCategoriesAction
    ): Response {
        $user = Auth::user();

        $resolvedTransaction = $detailAction->execute($user, $transaction);

        $systemCategories = $systemCategoriesAction->execute();
        $customCategories = $customCategoriesAction->execute($user->id);
        $allCategories = $systemCategories->merge($customCategories);
        $resolvedCategories = CategoryResource::collection($allCategories)->resolve();

        $transactionData = [
            'id' => $resolvedTransaction->id,
            'name' => $resolvedTransaction->name,
            'amount' => $resolvedTransaction->amount,
            'type' => $resolvedTransaction->type,
            'source' => $resolvedTransaction->source,
            'note' => $resolvedTransaction->note,
            'transactionAt' => $resolvedTransaction->transaction_at?->toIso8601String(),
            'categoryId' => $resolvedTransaction->category_id,
        ];

        return Inertia::render('Transaction/TransactionUpdate', [
            'transaction' => $transactionData,
            'categories' => $resolvedCategories,
        ]);
    }
}
