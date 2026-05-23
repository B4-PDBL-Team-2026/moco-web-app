<?php

namespace App\Http\Controllers\Web\Budgeting;

use App\Domains\Budgeting\Actions\CompleteOnboardingAction;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budgeting\StoreOnboardingRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OnboardingController extends Controller
{
    public function showOnboarding(): Response
    {
        $categories = Category::query()
            ->where(function (Builder $query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            })
            ->where('is_system', true)
            ->where('type', TransactionType::EXPENSE)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'type']);

        return Inertia::render('Budgeting/Onboarding/Onboarding', [
            'categories' => $categories,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function handleOnboarding(StoreOnboardingRequest $request, CompleteOnboardingAction $action): Response|RedirectResponse
    {
        $result = $action->execute(
            userId: auth()->user()->id,
            data: $request->toDTO(),
        );

        return Inertia::render('Budgeting/Onboarding/Onboarding', [
            'preview' => $result,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function completeOnboarding(StoreOnboardingRequest $request, CompleteOnboardingAction $action): Response|RedirectResponse
    {
        $action->execute(
            userId: auth()->user()->id,
            data: $request->toDTO(),
        );

        return redirect()->route('dashboard');
    }
}
