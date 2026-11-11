<?php

namespace App\Models;

use Database\Factories\RecurringTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'type', 'amount', 'currency', 'category_id', 'note', 'nature', 'frequency', 'interval', 'starts_on', 'ends_on', 'next_run_on', 'is_active'])]
class RecurringTransaction extends Model
{
    /** @use HasFactory<RecurringTransactionFactory> */
    use HasFactory;

    /**
     * Get the user that owns the recurring template.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category of the recurring template.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the transactions generated from the template.
     *
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recurring_id');
    }

    /**
     * Move the schedule to its next occurrence, deactivating it once it ends.
     */
    public function advance(): void
    {
        $next = match ($this->frequency) {
            'daily' => $this->next_run_on->copy()->addDays($this->interval),
            'weekly' => $this->next_run_on->copy()->addWeeks($this->interval),
            'yearly' => $this->next_run_on->copy()->addYearsNoOverflow($this->interval),
            default => $this->next_run_on->copy()->addMonthsNoOverflow($this->interval),
        };

        if ($this->ends_on !== null && $next->greaterThan($this->ends_on)) {
            $this->is_active = false;
        }

        $this->next_run_on = $next;
        $this->save();
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
            'interval' => 'integer',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'next_run_on' => 'date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }
}
