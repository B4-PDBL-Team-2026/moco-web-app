<?php

namespace App\Http\Controllers\Api\Budgeting;

use App\Domains\Budgeting\Actions\GetDashboardSummaryAction;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(GetDashboardSummaryAction $action)
    {
        $result = $action->execute(Auth::user(), CarbonImmutable::now());

        return $this->successResponse($result, 'Data retrieved successfully.');
    }
}
