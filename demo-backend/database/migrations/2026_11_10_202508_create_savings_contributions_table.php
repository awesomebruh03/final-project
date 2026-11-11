<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('savings_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('savings_goals')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('date');
            $table->text('note')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['goal_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_contributions');
    }
};
