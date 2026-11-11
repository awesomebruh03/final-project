<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = (float) $this->target_amount;
        $contributed = (float) ($this->contributions_sum_amount ?? 0);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_amount' => $target,
            'currency' => $this->currency,
            'target_date' => $this->target_date?->toDateString(),
            'status' => $this->status,
            'contributed' => $contributed,
            'remaining' => round(max($target - $contributed, 0), 2),
            'percentage' => $target > 0 ? round(($contributed / $target) * 100, 1) : 0.0,
        ];
    }
}
