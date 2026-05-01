<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Domains\Transaction\Actions\CreateTransactionAction;
use App\Domains\Transaction\Actions\DeleteTransactionAction;
use App\Domains\Transaction\Actions\GetAllTransactionAction;
use App\Domains\Transaction\Actions\GetTransactionDetailAction;
use App\Domains\Transaction\Actions\UpdateTransactionAction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Domains\Transaction\DTOs\UpdateTransactionData;
use App\Domains\Transaction\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\IndexTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Throwable;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(IndexTransactionRequest $request, GetAllTransactionAction $action): JsonResponse
    {
        Gate::authorize('view-any', Transaction::class);

        $filterData = FilterTransactionData::fromArray($request->validated());

        $result = $action->execute(Auth::id(), $filterData);

        return $this->success($result, 'Transactions retrieved successfully.');
    }

    /**
     * @throws Throwable
     */
    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        Gate::authorize('create', Transaction::class);

        $dto = CreateTransactionData::fromArray($request->validated());

        $result = $action->execute(Auth::user(), $dto);

        return $this->success(
            $result,
            'Transaction created successfully.',
            201
        );
    }

    public function show(Transaction $transaction, GetTransactionDetailAction $action): JsonResponse
    {
        Gate::authorize('view', $transaction);

        $result = $action->execute(Auth::user(), $transaction);

        return $this->success($result, 'Transaction retrieved successfully.');
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransactionAction $action): JsonResponse
    {
        Gate::authorize('update', $transaction);

        $dto = UpdateTransactionData::fromArray($request->validated());

        $result = $action->execute(Auth::user(), $transaction, $dto);

        return $this->success($result, 'Transaction updated successfully.');
    }

    /**
     * @throws Throwable
     */
    public function destroy(Transaction $transaction, DeleteTransactionAction $action): JsonResponse
    {
        Gate::authorize('delete', $transaction);

        $action->execute(Auth::user(), $transaction);

        // Return 204 No Content
        return response()->json(null, 204);
    }
}
