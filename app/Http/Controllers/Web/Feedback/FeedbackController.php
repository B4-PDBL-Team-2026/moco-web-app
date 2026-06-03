<?php

namespace App\Http\Controllers\Web\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Feedback/Create');
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $request->user()->feedbacks()->create($request->validated());

        return redirect()->back()->with('success', 'Terima kasih! Masukan Anda telah kami terima.');
    }
}