<?php

namespace App\Http\Controllers\Api\FixedCost;

use App\Domains\FixedCosts\Actions\CancelFixedCostPaymentAction;
use App\Domains\FixedCosts\Actions\ConfirmFixedCostPaymentAction;
use App\Domains\FixedCosts\Actions\CreateFixedCostTemplateAction;
use App\Domains\FixedCosts\Actions\DeleteFixedCostTemplateAction;
use App\Domains\FixedCosts\Actions\ListCurrentCycleOccurrencesAction;
use App\Domains\FixedCosts\Actions\ListFixedCostTemplateAction;
use App\Domains\FixedCosts\Actions\UpdateFixedCostOccurrenceAmountAction;
use App\Domains\FixedCosts\Actions\UpdateFixedCostOccurrenceMetadataAction;
use App\Domains\FixedCosts\Actions\UpdateFixedCostTemplateAction;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostOccurrenceAmountData;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostTemplateData;
use App\Http\Controllers\Controller;
use App\Http\Requests\FixedCost\IndexFixedCostTemplateRequest;
use App\Http\Requests\FixedCost\StoreFixedCostTemplateRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostOccurrenceAmountRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostOccurrenceMetadataRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostTemplateRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FixedCostController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/fixed-costs
     *
     * Returns a paginated, filterable list of the authenticated user's
     * fixed cost templates.
     *
     * Query parameters (all optional):
     *   - keyword   string  Filter by partial name match.
     *   - dueDay    int     Filter by exact due_day value (1–31).
     *   - cycleType string  Filter by cycle type (weekly|monthly).
     *   - isActive  bool    Filter by active status.
     *   - perPage   int     Items per page (default 15, max 100).
     *   - page      int     Page number (default 1).
     */
    public function index(
        IndexFixedCostTemplateRequest $request,
        ListFixedCostTemplateAction $action,
    ): JsonResponse {
        $result = $action->execute(
            userId: auth()->id(),
            filters: $request->toDto(),
        );

        return $this->success(data: $result, message: 'Fixed cost templates retrieved successfully.');
    }

    /**
     * POST /api/fixed-costs
     *
     * Create a new fixed cost template. Immediately generates the occurrence
     * for the current budget window and recalculates the snapshot (BR §12).
     *
     * @throws Throwable
     */
    public function store(
        StoreFixedCostTemplateRequest $request,
        CreateFixedCostTemplateAction $action,
    ): JsonResponse {
        $template = $action->execute(
            userId: $request->user()->id,
            fixedCost: CreateFixedCostTemplateData::fromArray($request->toDto()),
        );

        return $this->success(
            data: $template,
            message: 'Fixed cost template created successfully.',
            statusCode: 201,
        );
    }

    /**
     * PATCH /api/fixed-costs/{templateId}
     *
     * Sparse update of a template. Amount/due_day changes are deferred if a
     * paid occurrence already exists (BR §18).
     *
     * @throws Throwable
     */
    public function update(
        UpdateFixedCostTemplateRequest $request,
        int $templateId,
        UpdateFixedCostTemplateAction $action,
    ): JsonResponse {
        $action->execute(
            userId: $request->user()->id,
            templateId: $templateId,
            data: UpdateFixedCostTemplateData::fromArray($request->toDto()),
        );

        return $this->success(message: 'Fixed cost template updated successfully.');
    }

    /**
     * DELETE /api/fixed-costs/{templateId}
     *
     * Soft-deletes the template and voids any pending/overdue occurrences.
     * Paid occurrences are preserved for audit history.
     *
     * @throws Throwable
     */
    public function destroy(
        Request $request,
        int $templateId,
        DeleteFixedCostTemplateAction $action,
    ): JsonResponse {
        $action->execute(
            userId: $request->user()->id,
            templateId: $templateId,
        );

        return $this->success(message: 'Fixed cost template deleted successfully.');
    }

    /**
     * GET /api/fixed-costs/occurrences
     *
     * List all occurrences for the user's current budget cycle.
     */
    public function indexOccurrences(
        Request $request,
        ListCurrentCycleOccurrencesAction $action,
    ): JsonResponse {
        $occurrences = $action->execute(userId: $request->user()->id);

        return $this->success(data: $occurrences);
    }

    /**
     * POST /api/fixed-costs/occurrences/{occurrenceId}/confirm
     *
     * Confirms payment of an occurrence. Rejects if balance < amount (BR §13).
     * Creates a linked expense transaction and recalculates the snapshot (BR §14).
     *
     * @throws Throwable
     */
    public function confirmPayment(
        Request $request,
        int $occurrenceId,
        ConfirmFixedCostPaymentAction $action,
    ): JsonResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
        );

        return $this->success(message: 'Payment confirmed successfully.');
    }

    /**
     * POST /api/fixed-costs/occurrences/{occurrenceId}/cancel
     *
     * Cancels/voids a payment. Soft-deletes the linked transaction and
     * recalculates the snapshot (BR §14).
     *
     * Also serves as step 1 of the BR §17 edit-paid-amount flow.
     *
     * @throws Throwable
     */
    public function cancelPayment(
        Request $request,
        int $occurrenceId,
        CancelFixedCostPaymentAction $action,
    ): JsonResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
        );

        return $this->success(message: 'Payment cancelled successfully.');
    }

    /**
     * PATCH /api/fixed-costs/occurrences/{occurrenceId}/amount
     *
     * Step 2 of BR §17: update the amount of a voided occurrence.
     * The occurrence MUST be in VOID status (i.e. cancel was already called).
     * After this, call /confirm to complete the flow.
     *
     * @throws Throwable
     */
    public function updateOccurrenceAmount(
        UpdateFixedCostOccurrenceAmountRequest $request,
        int $occurrenceId,
        UpdateFixedCostOccurrenceAmountAction $action,
    ): JsonResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
            data: UpdateFixedCostOccurrenceAmountData::fromArray($request->toDto()),
        );

        return $this->success(message: 'Occurrence amount updated succesfully.');
    }

    /**
     * PATCH /api/fixed-costs/occurrences/{occurrenceId}/metadata
     *
     * Updates name/note only. No recalculation triggered (BR §15).
     */
    public function updateOccurrenceMetadata(
        UpdateFixedCostOccurrenceMetadataRequest $request,
        int $occurrenceId,
        UpdateFixedCostOccurrenceMetadataAction $action,
    ): JsonResponse {
        $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
            metadata: $request->toMetadata(),
        );

        return $this->success(message: 'Occurrence metadata updated successfully.');
    }
}
