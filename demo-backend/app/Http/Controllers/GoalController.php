<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContributionRequest;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Resources\ContributionResource;
use App\Http\Resources\GoalResource;
use App\Models\SavingsGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GoalController extends Controller
{
    /**
     * List the user's savings goals with their progress.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $goals = $request->user()
            ->savingsGoals()
            ->withSum('contributions', 'amount')
            ->orderByDesc('created_at')
            ->get();

        return GoalResource::collection($goals);
    }

    /**
     * Store a new savings goal for the authenticated user.
     */
    public function store(StoreGoalRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $goal = $user->savingsGoals()->create([
            'name' => $data['name'],
            'target_amount' => $data['target_amount'],
            'currency' => $user->currency,
            'target_date' => $data['target_date'] ?? null,
            'status' => 'active',
        ]);

        return (new GoalResource($goal))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * List the contributions made towards a goal.
     */
    public function contributions(Request $request, SavingsGoal $goal): AnonymousResourceCollection
    {
        abort_if($goal->user_id !== $request->user()->id, 404);

        return ContributionResource::collection(
            $goal->contributions()->orderByDesc('date')->orderByDesc('id')->get(),
        );
    }

    /**
     * Record a contribution towards a goal.
     */
    public function storeContribution(StoreContributionRequest $request, SavingsGoal $goal): JsonResponse
    {
        abort_if($goal->user_id !== $request->user()->id, 404);

        $data = $request->validated();

        $contribution = $goal->contributions()->create([
            'amount' => $data['amount'],
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
        ]);

        return (new ContributionResource($contribution))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete one of the authenticated user's savings goals.
     */
    public function destroy(Request $request, SavingsGoal $goal): JsonResponse
    {
        abort_if($goal->user_id !== $request->user()->id, 404);

        $goal->delete();

        return response()->json(['ok' => true]);
    }
}
