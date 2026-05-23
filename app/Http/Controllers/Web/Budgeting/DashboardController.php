<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function showDashboard(): Response
    {
        return Inertia::render('Budgeting/Dashboard/Dashboard');
    }
}
