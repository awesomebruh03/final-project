<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class TransactionResource extends JsonResource
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
            'date' => $this->date->toDateString(),
            'time' => $this->time === null ? null : Carbon::parse($this->time)->format('H:i'),
            'category' => $this->category->name,
            'note' => $this->note ?? '',
            'nature' => $this->nature,
        ];
    }
}
