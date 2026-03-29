<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Domains\Budgeting\Actions\GetDashboardSummaryAction;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(GetDashboardSummaryAction $action): JsonResponse
    {
        $result = $action->execute(Auth::user(), CarbonImmutable::now());

        return $this->success($result, 'Dashboard retrieved successfully.');
    }
}