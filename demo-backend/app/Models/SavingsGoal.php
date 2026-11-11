<?php

namespace App\Models;

use Database\Factories\SavingsGoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'target_amount', 'currency', 'target_date', 'status'])]
class SavingsGoal extends Model
{
    /** @use HasFactory<SavingsGoalFactory> */
    use HasFactory;

    /**
     * Get the user that owns the goal.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contributions made towards the goal.
     *
     * @return HasMany<SavingsContribution, $this>
     */
    public function contributions(): HasMany
    {
        return $this->hasMany(SavingsContribution::class, 'goal_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_amount' => 'float',
            'target_date' => 'date:Y-m-d',
        ];
    }
}
