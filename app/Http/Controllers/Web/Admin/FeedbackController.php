<?php

namespace App\Http\Controllers\Web\Admin;

use App\Domains\Feedback\Models\Feedback;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Feedback\RespondFeedbackRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Feedback::query()->with('user:id,name,email');

        $query->when($search, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('message', 'ilike', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%");
                    });
            });
        });

        $query->when($status, function ($q, $status) {
            $q->where('status', $status);
        });

        $query->when($category, function ($q, $category) {
            if ($category === 'Saran') {
                $q->where(function ($sub) {
                    $sub->where('category', 'like', '%Saran%')
                        ->orWhere('category', 'like', '%Masukan%');
                });
            } else {
                $q->where('category', 'like', "%{$category}%");
            }
        });

        $query->when($startDate, function ($q, $startDate) {
            $q->whereDate('created_at', '>=', $startDate);
        });

        $query->when($endDate, function ($q, $endDate) {
            $q->whereDate('created_at', '<=', $endDate);
        });

        $feedbacks = $query->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($item) => [
                'id' => $item->id,
                'created_at' => $item->created_at->format('Y-m-d H:i'),
                'user' => [
                    'name' => $item->user->name,
                    'email' => $item->user->email,
                ],
                'platform' => $item->platform,
                'category' => $item->category,
                'rating' => $item->rating,
                'message' => $item->message,
                'status' => $item->status,
                'admin_reply' => $item->admin_reply,
                'replied_at' => $item->replied_at ? $item->replied_at->format('Y-m-d H:i') : null,
            ]);

        // Aggregate Data for Charts
        $totalMasukan = Feedback::count();

        $avgRatingRow = Feedback::selectRaw('avg(rating) as avg_rating')->first();
        $avgRating = $avgRatingRow && $avgRatingRow->avg_rating !== null ? number_format((float) $avgRatingRow->avg_rating, 1) : '0.0';

        $webAppCount = Feedback::where('platform', 'like', '%Web App%')->count();
        $mobileCount = Feedback::where('platform', 'like', '%Mobile App%')->count();
        $totalPlatform = $webAppCount + $mobileCount;

        $platformData = [
            ['name' => 'Web App', 'value' => $totalPlatform > 0 ? round(($webAppCount / $totalPlatform) * 100) : 0, 'color' => '#10B981'],
            ['name' => 'Mobile Android', 'value' => $totalPlatform > 0 ? round(($mobileCount / $totalPlatform) * 100) : 0, 'color' => '#3B82F6'],
        ];

        $bugCount = Feedback::where('category', 'like', '%Bug%')->count();
        $fiturCount = Feedback::where('category', 'like', '%Fitur%')->count();
        $saranCount = Feedback::where('category', 'like', '%Saran%')->orWhere('category', 'like', '%Masukan%')->count();

        $categoryData = [
            ['name' => 'Bug', 'value' => $bugCount],
            ['name' => 'Fitur Baru', 'value' => $fiturCount],
            ['name' => 'Saran Umum', 'value' => $saranCount],
        ];

        return Inertia::render('Admin/Feedback/Index', [
            'feedbacks' => $feedbacks,
            'filters' => $request->only(['search', 'status', 'category', 'start_date', 'end_date']),
            'stats' => [
                'total_masukan' => $totalMasukan,
                'avg_rating' => $avgRating,
                'platform_data' => $platformData,
                'category_data' => $categoryData,
            ],
        ]);
    }

    public function respond(RespondFeedbackRequest $request, Feedback $feedback): RedirectResponse
    {
        $feedback->update([
            'status' => 'replied',
            'admin_reply' => $request->validated('admin_reply'),
            'replied_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Balasan berhasil dikirim.');
    }
}
