<?php

namespace App\Http\Controllers\Web\Admin;

use App\Domains\User\Services\DashboardStatsService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardStatsService $service): Response
    {
        $dashboardData = $service->getDashboardData();

        return Inertia::render('Admin/Dashboard', [
            'landingPageStats' => $dashboardData['landingPageStats'],
            'userStats' => $dashboardData['userStats'],
            'chartData' => $dashboardData['chartData'],
        ]);
    }
}
