<?php

namespace App\Models;

use Database\Factories\BudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['user_id', 'category_id', 'amount', 'currency', 'period', 'starts_on', 'ends_on'])]
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory;

    /**
     * Get the user that owns the budget.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category the budget applies to, if any.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the start and end date of the period that contains today.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function currentPeriod(): array
    {
        $today = now()->startOfDay();
        $start = $this->starts_on->copy()->startOfDay();
        $limit = $this->ends_on?->copy()->startOfDay();

        while (true) {
            $end = match ($this->period) {
                'weekly' => $start->copy()->addWeek()->subDay(),
                'yearly' => $start->copy()->addYearNoOverflow()->subDay(),
                default => $start->copy()->addMonthNoOverflow()->subDay(),
            };

            if ($limit !== null && $end->greaterThan($limit)) {
                $end = $limit->copy();
            }

            if ($today->lessThanOrEqualTo($end)) {
                return [$start->copy(), $end->copy()];
            }

            if ($limit !== null && $start->greaterThanOrEqualTo($limit)) {
                return [$start->copy(), $end->copy()];
            }

            $start = $end->copy()->addDay();
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
        ];
    }
}
