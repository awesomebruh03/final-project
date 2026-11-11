<?php

namespace App\Http\Controllers;

use App\Actions\ResolveCategory;
use App\Http\Requests\StoreIncomeRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncomeController extends Controller
{
    /**
     * List the authenticated user's income.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $incomes = $request->user()
            ->transactions()
            ->where('type', 'income')
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return TransactionResource::collection($incomes);
    }

    /**
     * Store a new income for the authenticated user.
     */
    public function store(StoreIncomeRequest $request, ResolveCategory $resolveCategory): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $category = $resolveCategory->handle($user, 'income', $data['category']);

        $income = $user->transactions()->create([
            'type' => 'income',
            'amount' => $data['amount'],
            'currency' => $user->currency,
            'date' => $data['date'],
            'time' => $data['time'] ?? null,
            'category_id' => $category->id,
            'note' => $data['note'] ?? null,
            'nature' => $data['nature'] ?? null,
        ]);

        return (new TransactionResource($income->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete one of the authenticated user's income records.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        abort_if(
            $transaction->user_id !== $request->user()->id || $transaction->type !== 'income',
            404,
        );

        $transaction->delete();

        return response()->json(['ok' => true]);
    }
}
