<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Http\Controllers\Controller;
use App\Domains\Budgeting\Actions\GetDashboardSummaryAction;
use App\Domains\Transaction\Actions\GetAllTransactionAction;
use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Http\Resources\Transaction\TransactionFeedResource;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Http\Resources\Category\CategoryResource;
use Illuminate\Support\Facades\Auth;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function showDashboard(
        GetDashboardSummaryAction $summaryAction,
        GetAllTransactionAction $transactionAction,
        GetAllSystemCategoriesAction $systemCategoriesAction,
        GetAllCustomCategoriesAction $customCategoriesAction
    ): Response {
        $user = Auth::user();
        $now = CarbonImmutable::now();

        // 1. Get dashboard summary metrics (already calculated by backend Action)
        $summary = $summaryAction->execute($user, $now);

        // 2. Fetch the 5 most recent transactions using the strict DTO
        $filterData = new FilterTransactionData(
            month: null,
            year: null,
            search: null,
            categoryId: null,
            perPage: 5,
            transactionType: null,
            transactionFeedType: null
        );
        $transactionsPagination = $transactionAction->execute($user->id, $filterData);

        // 3. Determine the base budget status for the Topbar badge
        $currentBalance = $summary['currentBalance'];
        $ceiling = $summary['safetyCeiling'];
        $flooring = $summary['safetyFlooring'];

        $budgetStatus = 'stabil';
        if ($currentBalance < 0) {
            $budgetStatus = 'defisit';
        } elseif ($currentBalance >= $ceiling) {
            $budgetStatus = 'surplus';
        } elseif ($currentBalance < $flooring) {
            $budgetStatus = 'kritis';
        }

        // 4. Resolve Eloquent records into clean camelCase array for React
        $resolvedTransactions = TransactionFeedResource::collection($transactionsPagination)->resolve();

        // 5. Retrieve and merge all system and user custom categories
        $systemCategories = $systemCategoriesAction->execute();
        $customCategories = $customCategoriesAction->execute($user->id);
        $allCategories = $systemCategories->merge($customCategories);
        $resolvedCategories = CategoryResource::collection($allCategories)->resolve();

        return Inertia::render('Budgeting/Dashboard/Dashboard', [
            'summary' => $summary,
            'recentTransactions' => $resolvedTransactions,
            'budgetStatus' => $budgetStatus,
            'categories' => $resolvedCategories,
        ]);
    }
}

