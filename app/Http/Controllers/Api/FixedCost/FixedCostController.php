<?php

namespace App\Http\Controllers\Api\FixedCost;

use App\Domains\FixedCost\Actions\CancelFixedCostPaymentAction;
use App\Domains\FixedCost\Actions\ConfirmFixedCostPaymentAction;
use App\Domains\FixedCost\Actions\CreateFixedCostTemplateAction;
use App\Domains\FixedCost\Actions\DeleteFixedCostTemplateAction;
use App\Domains\FixedCost\Actions\GetAllFixedCostOccurrencesAction;
use App\Domains\FixedCost\Actions\GetAllFixedCostTemplatesAction;
use App\Domains\FixedCost\Actions\UpdateFixedCostOccurrenceAmountAction;
use App\Domains\FixedCost\Actions\UpdateFixedCostOccurrenceMetadataAction;
use App\Domains\FixedCost\Actions\UpdateFixedCostTemplateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FixedCost\IndexFixedCostTemplateRequest;
use App\Http\Requests\FixedCost\StoreFixedCostTemplateRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostOccurrenceAmountRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostOccurrenceMetadataRequest;
use App\Http\Requests\FixedCost\UpdateFixedCostTemplateRequest;
use App\Http\Requests\IndexFixedCostOccurrenceRequest;
use App\Http\Resources\FixedCost\FixedCostOccurrenceResource;
use App\Http\Resources\FixedCost\FixedCostTemplateResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Throwable;

class FixedCostController extends Controller
{
    /**
     * Get list of fixed cost templates.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: array<FixedCostTemplateResource>,
     *     meta: array{
     *         currentPage: int,
     *         lastPage: int,
     *         perPage: int,
     *         total: int,
     *         hasMore: bool
     *     }
     * }
     */
    public function index(
        IndexFixedCostTemplateRequest $request,
        GetAllFixedCostTemplatesAction $action,
    ): ApiResponse {
        $result = $action->execute(
            userId: auth()->id(),
            filters: $request->toDTO(),
        );

        return $this->successResponse(
            data: FixedCostTemplateResource::collection($result),
            message: 'Fixed cost templates retrieved successfully.'
        );
    }

    /**
     * Create a new fixed cost template.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: FixedCostTemplateResource
     * }
     *
     * @throws Throwable
     */
    public function store(
        StoreFixedCostTemplateRequest $request,
        CreateFixedCostTemplateAction $action,
    ): ApiResponse {
        $template = $action->execute(
            userId: $request->user()->id,
            fixedCost: $request->toDTO(),
        );

        return $this->successResponse(
            data: FixedCostTemplateResource::make($template),
            message: 'Fixed cost template created successfully.',
            status: 201,
        );
    }

    /**
     * PATCH /api/fixed-costs/{templateId}
     *
     * Sparse update of a template. Amount/due_day changes are deferred if a
     * paid occurrence already exists.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: FixedCostResource
     * }
     *
     * @throws Throwable
     */
    public function update(
        UpdateFixedCostTemplateRequest $request,
        int $templateId,
        UpdateFixedCostTemplateAction $action,
    ): ApiResponse {
        $result = $action->execute(
            userId: $request->user()->id,
            templateId: $templateId,
            data: $request->toDTO(),
        );

        return $this->successResponse(
            data: FixedCostTemplateResource::make($result),
            message: 'Fixed cost template updated successfully.',
        );
    }

    /**
     * DELETE /api/fixed-costs/{templateId}
     *
     * Soft-deletes the template and voids any pending/overdue occurrences.
     * Paid occurrences are preserved for audit history.
     *
     * @response array{ message: string, status: bool }
     *
     * @throws Throwable
     */
    public function destroy(
        Request $request,
        int $templateId,
        DeleteFixedCostTemplateAction $action,
    ): ApiResponse {
        $action->execute(
            userId: $request->user()->id,
            templateId: $templateId,
        );

        return $this->successResponse(message: 'Fixed cost template deleted successfully.');
    }

    /**
     * GET /api/fixed-costs/occurrences
     *
     * List all non-void fixed cost occurrences.
     *
     * @response array{
     *      success: bool,
     *      message: string,
     *      data: array<FixedCostOccurrenceResource>,
     *      meta: array{
     *          currentPage: int,
     *          lastPage: int,
     *          perPage: int,
     *          total: int,
     *          hasMore: bool
     *      }
     * }
     */
    public function indexOccurrences(
        IndexFixedCostOccurrenceRequest $request,
        GetAllFixedCostOccurrencesAction $action,
    ): ApiResponse {
        $occurrences = $action->execute(
            userId: $request->user()->id,
            filters: $request->toDTO(),
        );

        return $this->successResponse(
            data: FixedCostOccurrenceResource::collection($occurrences),
            message: 'Fixed cost occurrences retrieved successfully.'
        );
    }

    /**
     * Confirm occurrence payment
     *
     * Confirms payment of an occurrence. Rejects if balance < amount (BR §13).
     * Creates a linked expense transaction and recalculates the snapshot (BR §14).
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: FixedCostOccurrenceResource
     * }
     *
     * @throws Throwable
     */
    public function confirmPayment(
        Request $request,
        int $occurrenceId,
        ConfirmFixedCostPaymentAction $action,
    ): ApiResponse {
        $result = $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
        );

        return $this->successResponse(
            data: FixedCostOccurrenceResource::make($result),
            message: 'Payment confirmed successfully.',
        );
    }

    /**
     * Cancel occurrence payment
     *
     * Cancels/voids a payment. Soft-deletes the linked transaction and
     * recalculates the snapshot.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: FixedCostOccurrenceResource
     * }
     *
     * @throws Throwable
     */
    public function cancelPayment(
        Request $request,
        int $occurrenceId,
        CancelFixedCostPaymentAction $action,
    ): ApiResponse {
        $result = $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
        );

        return $this->successResponse(
            data: FixedCostOccurrenceResource::make($result),
            message: 'Payment cancelled successfully.',
        );
    }

    /**
     * Update occurrence amount
     *
     * Updates the exact billing amount for a specific occurrence.
     * If the occurrence is already paid and the amount is increased,
     * it will check if the user has sufficient balance (BR §17).
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: FixedCostOccurrenceResource
     * }
     *
     * @throws Throwable
     */
    public function updateOccurrenceAmount(
        UpdateFixedCostOccurrenceAmountRequest $request,
        int $occurrenceId,
        UpdateFixedCostOccurrenceAmountAction $action,
    ): ApiResponse {
        $result = $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
            data: $request->toDTO(),
        );

        return $this->successResponse(
            data: FixedCostOccurrenceResource::make($result),
            message: 'Occurrence amount updated succesfully.',
        );
    }

    /**
     * Update occurrence metadata
     *
     * Updates non-financial metadata (name and note) on a fixed cost occurrence.
     * Changing these fields does not trigger any recalculation.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: FixedCostOccurrenceResource
     * }
     *
     * @throws Throwable
     */
    public function updateOccurrenceMetadata(
        UpdateFixedCostOccurrenceMetadataRequest $request,
        int $occurrenceId,
        UpdateFixedCostOccurrenceMetadataAction $action,
    ): ApiResponse {
        $result = $action->execute(
            userId: $request->user()->id,
            occurrenceId: $occurrenceId,
            data: $request->toDTO(),
        );

        return $this->successResponse(
            data: FixedCostOccurrenceResource::make($result),
            message: 'Occurrence metadata updated successfully.',
        );
    }
}
