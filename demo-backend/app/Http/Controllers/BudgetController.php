<?php

namespace App\Http\Controllers;

use App\Actions\ResolveCategory;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BudgetController extends Controller
{
    /**
     * List the user's budgets with their spending for the current period.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $budgets = $request->user()
            ->budgets()
            ->with('category')
            ->orderBy('starts_on')
            ->get()
            ->map(fn (Budget $budget): BudgetResource => $this->toResource($request, $budget));

        return BudgetResource::collection($budgets);
    }

    /**
     * Store a new budget for the authenticated user.
     */
    public function store(StoreBudgetRequest $request, ResolveCategory $resolveCategory): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $category = empty($data['category'])
            ? null
            : $resolveCategory->handle($user, 'expense', $data['category']);

        $budget = $user->budgets()->create([
            'category_id' => $category?->id,
            'amount' => $data['amount'],
            'currency' => $user->currency,
            'period' => $data['period'],
            'starts_on' => $data['starts_on'] ?? now()->toDateString(),
            'ends_on' => $data['ends_on'] ?? null,
        ]);

        return $this->toResource($request, $budget)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete one of the authenticated user's budgets.
     */
    public function destroy(Request $request, Budget $budget): JsonResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 404);

        $budget->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Build the resource for a budget and its current period spending.
     */
    private function toResource(Request $request, Budget $budget): BudgetResource
    {
        [$start, $end] = $budget->currentPeriod();

        $spent = (float) $request->user()
            ->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($budget->category_id, fn ($query) => $query->where('category_id', $budget->category_id))
            ->sum('amount');

        $budget->loadMissing('category');

        return new BudgetResource($budget, $start, $end, $spent);
    }
}
