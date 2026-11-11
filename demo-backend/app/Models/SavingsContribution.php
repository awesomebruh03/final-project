<?php

namespace App\Models;

use Database\Factories\SavingsContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['goal_id', 'amount', 'date', 'note', 'transaction_id'])]
class SavingsContribution extends Model
{
    /** @use HasFactory<SavingsContributionFactory> */
    use HasFactory;

    /**
     * Get the goal the contribution belongs to.
     *
     * @return BelongsTo<SavingsGoal, $this>
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(SavingsGoal::class, 'goal_id');
    }

    /**
     * Get the transaction linked to the contribution, if any.
     *
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
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
            'date' => 'date:Y-m-d',
        ];
    }
}
