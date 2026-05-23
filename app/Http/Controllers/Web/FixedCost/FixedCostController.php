<?php

namespace App\Http\Controllers\Web\FixedCost;

use App\Domains\FixedCost\Actions\CancelFixedCostPaymentAction;
use App\Domains\FixedCost\Actions\ConfirmFixedCostPaymentAction;
use App\Domains\FixedCost\Actions\CreateFixedCostTemplateAction;
use App\Domains\FixedCost\Actions\DeleteFixedCostTemplateAction;
use App\Domains\FixedCost\Actions\GetAllFixedCostOccurrencesAction;
use App\Domains\FixedCost\Actions\UpdateFixedCostOccurrenceAmountAction;
use App\Domains\FixedCost\Actions\UpdateFixedCostOccurrenceMetadataAction;
use App\Domains\FixedCost\Actions\UpdateFixedCostTemplateAction;
use App\Domains\FixedCost\DTOs\FilterFixedCostOccurrenceData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Http\Controllers\Controller;
use App\Http\Requests\FixedCost\IndexFixedCostOccurrenceRequest;
use App\Http\Requests\FixedCost\StoreFixedCostTemplateRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostOccurrenceAmountRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostOccurrenceMetadataRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostTemplateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class FixedCostController extends Controller
{
    /**
     * Show the fixed cost occurrences page.
     * Reuses IndexFixedCostOccurrenceRequest for validation.
     * Tab filter overrides the status field from the request.
     */
    public function index(
        IndexFixedCostOccurrenceRequest $request,
        GetAllFixedCostOccurrencesAction $action,
    ): Response {
        $userId = auth()->id();
        $tab = $request->input('tab', 'pending');

        $tabStatusMap = [
            'pending' => FixedCostOccurenceStatus::PENDING,
            'paid' => FixedCostOccurenceStatus::PAID,
            'skipped' => FixedCostOccurenceStatus::SKIPPED,
        ];

        $activeStatus = $tabStatusMap[$tab] ?? FixedCostOccurenceStatus::PENDING;

        // Main listing — status driven by active tab, other filters from request
        $listingDTO = new FilterFixedCostOccurrenceData(
            keyword: $request->validated('keyword') ?? null,
            status: $activeStatus,
            startDate: $request->validated('startDate') ?? null,
            endDate: $request->validated('endDate') ?? null,
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('perPage', 15),
        );

        $occurrences = $action->execute(userId: $userId, filters: $listingDTO);

        // Badge counts per tab — reuse same action with status-only filter
        $counts = array_map(function ($status) use ($request, $action, $userId) {
            return $action->execute(
                userId: $userId,
                filters: new FilterFixedCostOccurrenceData(
                    keyword: null,
                    status: $status,
                    startDate: $request->validated('startDate') ?? null,
                    endDate: $request->validated('endDate') ?? null,
                    page: 1,
                    perPage: 1000,
                ),
            )->total();
        }, $tabStatusMap);

        $snapshot = auth()->user()->budgetSnapshot;

        $mapped = collect($occurrences->items())->map(fn ($occurrence) => [
            'id' => $occurrence->id,
            'name' => $occurrence->name,
            'amount' => $occurrence->amount,
            'dueDate' => $occurrence->due_date,
            'status' => $occurrence->status->value,
            'categoryName' => $occurrence->category?->name,
            'categoryIcon' => $occurrence->category?->icon,
            'note' => $occurrence->note ?? null,
        ]);

        return Inertia::render('FixedCost/FixedCostIndex', [
            'occurrences' => $mapped,
            'counts' => $counts,
            'filters' => [
                'tab' => $tab,
                'keyword' => $request->validated('keyword') ?? null,
                'startDate' => $request->validated('startDate') ?? null,
                'endDate' => $request->validated('endDate') ?? null,
            ],
            'status' => $snapshot?->financial_condition,
        ]);
    }

    /**
     * Create a new fixed cost template.
     *
     * @throws Throwable
     */
    public function store(
        StoreFixedCostTemplateRequest $request,
        CreateFixedCostTemplateAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            fixedCost: $request->toDTO(),
        );

        return back()->with('success', 'Biaya tetap berhasil ditambahkan.');
    }

    /**
     * Sparse PATCH update of a fixed cost template.
     *
     * @throws Throwable
     */
    public function update(
        UpdateFixedCostTemplateRequest $request,
        int $templateId,
        UpdateFixedCostTemplateAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            templateId: $templateId,
            data: $request->toDTO(),
        );

        return back()->with('success', 'Biaya tetap berhasil diperbarui.');
    }

    /**
     * Soft-delete a fixed cost template.
     *
     * @throws Throwable
     */
    public function destroy(
        Request $request,
        int $templateId,
        DeleteFixedCostTemplateAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            templateId: $templateId,
        );

        return back()->with('success', 'Biaya tetap berhasil dihapus.');
    }

    /**
     * Confirm payment for an occurrence.
     *
     * @throws Throwable
     */
    public function confirmPayment(
        Request $request,
        int $occurrenceId,
        ConfirmFixedCostPaymentAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
        );

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi.');
    }

    /**
     * Cancel/void a paid occurrence.
     *
     * @throws Throwable
     */
    public function cancelPayment(
        Request $request,
        int $occurrenceId,
        CancelFixedCostPaymentAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
        );

        return back()->with('success', 'Pembayaran berhasil dibatalkan.');
    }

    /**
     * Skip a pending/overdue occurrence.
     * Direct status transition — no dedicated action exists for this.
     */
    public function skipOccurrence(
        Request $request,
        int $occurrenceId,
    ): RedirectResponse {
        FixedCostOccurrence::query()
            ->where('id', $occurrenceId)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
            ])
            ->firstOrFail()
            ->update(['status' => FixedCostOccurenceStatus::SKIPPED->value]);

        return back()->with('success', 'Tagihan dilewati.');
    }

    /**
     * Update billing amount on a specific occurrence.
     * Delegates to UpdateFixedCostOccurrenceAmountRequest::toDTO().
     *
     * @throws Throwable
     */
    public function updateOccurrenceAmount(
        UpdateFixedCostOccurrenceAmountRequest $request,
        int $occurrenceId,
        UpdateFixedCostOccurrenceAmountAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
            data: $request->toDTO(),
        );

        return back()->with('success', 'Jumlah tagihan berhasil diperbarui.');
    }

    /**
     * Update non-financial metadata (name, note) on an occurrence.
     * Delegates to UpdateFixedCostOccurrenceMetadataRequest::toDTO().
     *
     * @throws Throwable
     */
    public function updateOccurrenceMetadata(
        UpdateFixedCostOccurrenceMetadataRequest $request,
        int $occurrenceId,
        UpdateFixedCostOccurrenceMetadataAction $action,
    ): RedirectResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
            data: $request->toDTO(),
        );

        return back()->with('success', 'Metadata tagihan berhasil diperbarui.');
    }
}
