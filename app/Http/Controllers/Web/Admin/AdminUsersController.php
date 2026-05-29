<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminUsersController extends Controller
{
    /**
     * Display the admin users management page.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Users');
    }
}
