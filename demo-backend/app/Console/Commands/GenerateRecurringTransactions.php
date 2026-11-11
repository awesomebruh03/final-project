<?php

namespace App\Console\Commands;

use App\Actions\GenerateDueRecurringTransactions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('recurring:generate')]
#[Description('Materialize recurring transactions that are due')]
class GenerateRecurringTransactions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GenerateDueRecurringTransactions $generate): int
    {
        $created = $generate->handle();

        $this->info("Generated {$created} recurring transaction(s).");

        return self::SUCCESS;
    }
}
