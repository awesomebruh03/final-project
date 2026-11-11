<?php

namespace App\Http\Controllers;

use App\Actions\ResolveCategory;
use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Resources\RecurringTransactionResource;
use App\Models\RecurringTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecurringTransactionController extends Controller
{
    /**
     * List the user's recurring transaction templates.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = $request->user()
            ->recurringTransactions()
            ->with('category')
            ->orderBy('next_run_on')
            ->get();

        return RecurringTransactionResource::collection($templates);
    }

    /**
     * Store a new recurring transaction template.
     */
    public function store(StoreRecurringTransactionRequest $request, ResolveCategory $resolveCategory): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $category = $resolveCategory->handle($user, $data['type'], $data['category']);

        $template = $user->recurringTransactions()->create([
            'type' => $data['type'],
            'amount' => $data['amount'],
            'currency' => $user->currency,
            'category_id' => $category->id,
            'note' => $data['note'] ?? null,
            'nature' => $data['nature'] ?? null,
            'frequency' => $data['frequency'],
            'interval' => $data['interval'] ?? 1,
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'] ?? null,
            'next_run_on' => $data['starts_on'],
            'is_active' => true,
        ]);

        return (new RecurringTransactionResource($template->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete one of the authenticated user's recurring templates.
     */
    public function destroy(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        abort_if($recurring->user_id !== $request->user()->id, 404);

        $recurring->delete();

        return response()->json(['ok' => true]);
    }
}
