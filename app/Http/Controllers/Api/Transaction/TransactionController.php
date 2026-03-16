<?php

namespace App\Http\Controllers\Api\Transaction;

use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\GetAllTransactionAction;
use App\DTOs\Transaction\CreateTransactionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\IndexTransactionRequest;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    use ApiResponse;

    public function index(IndexTransactionRequest $request, GetAllTransactionAction $action): JsonResponse
    {
        $result = $action->execute(auth()->user(), $request->validated());

        return $this->success($result, 'Transactions retrieved successfully.');
    }

    public function store(StoreTransactionRequest $request, CreateTransactionAction $action): JsonResponse
    {
        $dto = CreateTransactionData::fromArray($request->validated());

        $result = $action->execute(auth()->user(), $dto);

        return $this->success(
            $result,
            'Transaction created successfully.',
        );
    }

    public function show($id): JsonResponse
    {
        $transaction = Transaction::with('category')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    public function update($id, UpdateTransactionRequest $request): JsonResponse
    {
        $transaction = Transaction::where('user_id', Auth::id())
            ->findOrFail($id);

        $transaction->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $transaction,
            'message' => 'Transaction updated successfully',
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $transaction = Transaction::where('user_id', Auth::id())
            ->findOrFail($id);

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaction deleted successfully',
        ]);
    }
}
