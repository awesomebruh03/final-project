<?php

namespace App\Http\Controllers;

use App\Actions\ResolveCategory;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExpenseController extends Controller
{
    /**
     * List the authenticated user's expenses.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $expenses = $request->user()
            ->transactions()
            ->where('type', 'expense')
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return TransactionResource::collection($expenses);
    }

    /**
     * Store a new expense for the authenticated user.
     */
    public function store(StoreExpenseRequest $request, ResolveCategory $resolveCategory): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $category = $resolveCategory->handle($user, 'expense', $data['category']);

        $expense = $user->transactions()->create([
            'type' => 'expense',
            'amount' => $data['amount'],
            'currency' => $user->currency,
            'date' => $data['date'],
            'time' => $data['time'] ?? null,
            'category_id' => $category->id,
            'note' => $data['note'] ?? null,
            'nature' => $data['nature'] ?? null,
        ]);

        return (new TransactionResource($expense->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete one of the authenticated user's expenses.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        abort_if(
            $transaction->user_id !== $request->user()->id || $transaction->type !== 'expense',
            404,
        );

        $transaction->delete();

        return response()->json(['ok' => true]);
    }
}
