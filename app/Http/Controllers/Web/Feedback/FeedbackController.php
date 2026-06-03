<?php

namespace App\Http\Controllers\Web\Feedback;

use App\Http\Controllers\Controller;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Mail\FeedbackReceivedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Feedback/Create');
    }

    public function store(StoreFeedbackRequest $request): RedirectResponse
    {
        $feedback = $request->user()->feedbacks()->create($request->validated());

        Mail::to($request->user()->email)->send(new FeedbackReceivedMail($feedback));

        return redirect()->back()->with('success', 'Terima kasih! Masukan Anda telah kami terima.');
    }
}
