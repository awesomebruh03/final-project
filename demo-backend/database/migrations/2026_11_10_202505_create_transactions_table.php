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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('expense');
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->date('date');
            $table->time('time')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->text('note')->nullable();
            $table->string('nature', 10)->nullable();
            $table->foreignId('recurring_id')->nullable()->constrained('recurring_transactions')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'type', 'date']);
            $table->index(['user_id', 'category_id', 'date']);
            $table->unique(['recurring_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
