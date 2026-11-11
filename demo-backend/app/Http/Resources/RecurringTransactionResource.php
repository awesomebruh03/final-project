<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'category' => $this->category?->name,
            'note' => $this->note ?? '',
            'nature' => $this->nature,
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'starts_on' => $this->starts_on->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'next_run_on' => $this->next_run_on->toDateString(),
            'is_active' => $this->is_active,
        ];
    }
}
