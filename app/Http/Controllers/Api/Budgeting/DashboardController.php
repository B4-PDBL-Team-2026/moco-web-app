<?php

namespace App\Http\Controllers\Api\Budgeting;

use App\Domains\Budgeting\Actions\GetDashboardSummaryAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Budgeting\DashboardDataResource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get user dashboard summary.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: DashboardDataResource
     * }
     */
    public function index(GetDashboardSummaryAction $action)
    {
        $result = $action->execute(Auth::user(), CarbonImmutable::now());

        return $this->successResponse(DashboardDataResource::make($result), 'Data retrieved successfully.');
    }
}
