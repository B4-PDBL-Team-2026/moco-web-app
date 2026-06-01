<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Domains\Budgeting\Actions\GetDashboardSummaryAction;
use App\Domains\Category\Actions\GetAllCustomCategoriesAction;
use App\Domains\Category\Actions\GetAllSystemCategoriesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Category\CategoryResource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function showDashboard(
        GetDashboardSummaryAction $action,
        GetAllSystemCategoriesAction $systemCategoriesAction,
        GetAllCustomCategoriesAction $customCategoriesAction,
    ): Response {
        $data = $action->execute(Auth::user(), CarbonImmutable::now());

        $status = match (true) {
            $data['currentBalance'] > $data['safetyCeiling'] => 'surplus',
            $data['currentBalance'] >= $data['safetyFlooring'] => 'stabil',
            $data['currentBalance'] > 0 => 'kritis',
            default => 'defisit',
        };

        $categories = CategoryResource::collection(
            $systemCategoriesAction->execute()->merge(
                $customCategoriesAction->execute(Auth::id())
            )
        )->resolve();

        return Inertia::render('Budgeting/Dashboard/Dashboard', [
            ...$data,
            'status' => $status,
            'categories' => $categories,
        ]);
    }
}
