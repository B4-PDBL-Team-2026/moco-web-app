<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Domains\Transaction\Actions\CreateBatchTransactionAction;
use App\Domains\Transaction\Actions\CreateTransactionAction;
use App\Domains\Transaction\Actions\DeleteTransactionAction;
use App\Domains\Transaction\Actions\GetAllTransactionAction;
use App\Domains\Transaction\Actions\GetBatchTransactionDetailAction;
use App\Domains\Transaction\Actions\GetTransactionDetailAction;
use App\Domains\Transaction\Actions\UpdateTransactionAction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Domains\Transaction\DTOs\UpdateTransactionData;
use App\Domains\Transaction\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\IndexTransactionRequest;
use App\Http\Requests\Transaction\StoreBatchTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Http\Resources\Transaction\TransactionBatchResource;
use App\Http\Resources\Transaction\TransactionFeedResource;
use App\Http\Resources\Transaction\TransactionResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Handles HTTP requests for Transaction management.
 */
class TransactionController extends Controller
{
    /**
     * Retrieve a paginated and filtered list of user transactions.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: array<TransactionFeedResource>,
     *     meta: array{
     *         currentPage: int,
     *         lastPage: int,
     *         perPage: int,
     *         total: int,
     *         hasMore: bool
     *     }
     * }
     */
    public function index(IndexTransactionRequest $request, GetAllTransactionAction $action): ApiResponse
    {
        Gate::authorize('view-any', Transaction::class);

        $filterData = FilterTransactionData::fromArray($request->validated());

        $result = $action->execute(Auth::id(), $filterData);

        return $this->successResponse(
            TransactionFeedResource::collection($result),
            'Transactions retrieved successfully.'
        );
    }

    /**
     * @throws Throwable
     */
    public function storeBatch(StoreBatchTransactionRequest $request, CreateBatchTransactionAction $action): ApiResponse
    {
        $batch = $action->execute(
            userId: $request->user()->id,
            data: $request->toDTO()
        );

        // Make sure to create a TransactionBatchResource later
        return $this->successResponse(
            data: TransactionBatchResource::make($batch),
            message: 'Batch transactions saved successfully.',
            status: 201
        );
    }

    /**
     * Create a batch of transactions from a scanned receipt.
     *
     * @throws Throwable
     *
     * @response array{
     * success: bool,
     * message: string,
     * data: TransactionBatchResource
     * }
     */
    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): ApiResponse
    {
        Gate::authorize('create', Transaction::class);

        $dto = CreateTransactionData::fromArray($request->validated());

        $result = $action->execute(Auth::user(), $dto);

        return $this->successResponse(
            TransactionResource::make($result),
            'Transaction created successfully.',
            201
        );
    }

    /**
     * Display the specified transaction detail.
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: TransactionResource
     * }
     */
    public function show(Transaction $transaction, GetTransactionDetailAction $action): ApiResponse
    {
        Gate::authorize('view', $transaction);

        $result = $action->execute(Auth::user(), $transaction);

        return $this->successResponse(
            TransactionResource::make($result),
            'Transaction retrieved successfully.'
        );
    }

    /**
     * Update the specified transaction in storage.
     *
     * @throws Throwable
     *
     * @response array{
     *     success: bool,
     *     message: string,
     *     data: TransactionResource
     * }
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransactionAction $action): ApiResponse
    {
        Gate::authorize('update', $transaction);

        $dto = UpdateTransactionData::fromArray($request->validated());

        $result = $action->execute(Auth::user(), $transaction, $dto);

        return $this->successResponse(
            TransactionResource::make($result),
            'Transaction updated successfully.'
        );
    }

    /**
     * Remove the specified transaction from storage.
     *
     * @throws Throwable
     *
     * @response array{
     *     success: bool,
     *     message: string
     * }
     */
    public function destroy(Transaction $transaction, DeleteTransactionAction $action): ApiResponse
    {
        Gate::authorize('delete', $transaction);

        $action->execute(Auth::user(), $transaction);

        return $this->successResponse(
            message: 'Transaction deleted successfully.',
            status: 204
        );
    }
}
