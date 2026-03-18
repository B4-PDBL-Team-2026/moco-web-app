<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Domains\Transactions\Actions\CreateTransactionAction;
use App\Domains\Transactions\Actions\DeleteTransactionAction;
use App\Domains\Transactions\Actions\GetAllTransactionAction;
use App\Domains\Transactions\Actions\GetTransactionDetailAction;
use App\Domains\Transactions\Actions\UpdateTransactionAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\DTOs\FilterTransactionData;
use App\Domains\Transactions\DTOs\UpdateTransactionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\IndexTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(IndexTransactionRequest $request, GetAllTransactionAction $action): JsonResponse
    {
        Gate::authorize('view-any', Transaction::class);

        $filterData = FilterTransactionData::fromArray($request->validated());

        $result = $action->execute(auth()->id(), $filterData);

        return $this->success($result, 'Transactions retrieved successfully.');
    }

    /**
     * @throws Throwable
     */
    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        Gate::authorize('create', Transaction::class);

        $dto = CreateTransactionData::fromArray($request->validated());

        $result = $action->execute(auth()->user(), $dto);

        return $this->success(
            $result,
            'Transaction created successfully.',
        );
    }

    public function show(Transaction $transaction, GetTransactionDetailAction $action): JsonResponse
    {
        Gate::authorize('view', $transaction);

        $result = $action->execute(auth()->user(), $transaction);

        return $this->success($result, 'Transaction retrieved successfully.');
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransactionAction $action): JsonResponse
    {
        Gate::authorize('update', $transaction);

        $dto = UpdateTransactionData::fromArray($request->validated());

        $result = $action->execute(auth()->user(), $transaction, $dto);

        return $this->success($result, 'Transaction updated successfully.');
    }

    /**
     * @throws Throwable
     */
    public function destroy(Transaction $transaction, DeleteTransactionAction $action): JsonResponse
    {
        Gate::authorize('delete', $transaction);

        $action->execute(auth()->user(), $transaction);

        return $this->success(message: 'Transaction deleted successfully.');
    }
}
