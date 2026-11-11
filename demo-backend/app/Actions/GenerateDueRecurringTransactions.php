<?php

namespace App\Actions;

use App\Models\RecurringTransaction;

class GenerateDueRecurringTransactions
{
    /**
     * Materialize every recurring occurrence that is due today or earlier.
     *
     * @return int The number of transactions created.
     */
    public function handle(): int
    {
        $today = now()->startOfDay();
        $created = 0;

        RecurringTransaction::query()
            ->where('is_active', true)
            ->whereDate('next_run_on', '<=', $today)
            ->each(function (RecurringTransaction $template) use ($today, &$created): void {
                while ($template->is_active && $template->next_run_on->lessThanOrEqualTo($today)) {
                    $transaction = $template->transactions()->firstOrCreate(
                        ['date' => $template->next_run_on->toDateString()],
                        [
                            'user_id' => $template->user_id,
                            'type' => $template->type,
                            'amount' => $template->amount,
                            'currency' => $template->currency,
                            'category_id' => $template->category_id,
                            'note' => $template->note,
                            'nature' => $template->nature,
                        ],
                    );

                    if ($transaction->wasRecentlyCreated) {
                        $created++;
                    }

                    $template->advance();
                }
            });

        return $created;
    }
}
