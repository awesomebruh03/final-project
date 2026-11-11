<?php

namespace App\Http\Resources;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class BudgetResource extends JsonResource
{
    /**
     * @param  Carbon  $periodStart  The first day of the period containing today.
     * @param  Carbon  $periodEnd  The last day of the period containing today.
     * @param  float  $spent  Expenses recorded inside the period.
     */
    public function __construct(
        Budget $budget,
        private readonly Carbon $periodStart,
        private readonly Carbon $periodEnd,
        private readonly float $spent,
    ) {
        parent::__construct($budget);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $amount = (float) $this->amount;

        return [
            'id' => $this->id,
            'category' => $this->category?->name,
            'amount' => $amount,
            'currency' => $this->currency,
            'period' => $this->period,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'spent' => $this->spent,
            'remaining' => round($amount - $this->spent, 2),
            'percentage' => $amount > 0 ? round(($this->spent / $amount) * 100, 1) : 0.0,
        ];
    }
}
