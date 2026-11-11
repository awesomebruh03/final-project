<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    /**
     * Summarize income, expenses, savings and breakdowns for a date range.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $user = $request->user();
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->endOfMonth()->toDateString();

        $transactions = Transaction::query()
            ->where('transactions.user_id', $user->id)
            ->whereBetween('transactions.date', [$from, $to]);

        $income = (float) (clone $transactions)
            ->where('transactions.type', 'income')
            ->sum('transactions.amount');

        $expenses = (float) (clone $transactions)
            ->where('transactions.type', 'expense')
            ->sum('transactions.amount');

        $savings = (float) $user->savingsContributions()
            ->whereBetween('savings_contributions.date', [$from, $to])
            ->sum('amount');

        $byCategory = (clone $transactions)
            ->where('transactions.type', 'expense')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->selectRaw('categories.name as category, SUM(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'category' => $row->category,
                'total' => (float) $row->total,
            ])
            ->values();

        $byHour = (clone $transactions)
            ->where('transactions.type', 'expense')
            ->whereNotNull('transactions.time')
            ->selectRaw('substr(transactions.time, 1, 2) as hour, SUM(transactions.amount) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn ($row): array => [
                'hour' => (int) $row->hour,
                'total' => (float) $row->total,
            ])
            ->values();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'currency' => $user->currency,
            'income' => $income,
            'expenses' => $expenses,
            'net' => round($income - $expenses, 2),
            'savings' => $savings,
            'by_category' => $byCategory,
            'by_hour' => $byHour,
        ]);
    }
}
