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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('period', 10)->default('monthly');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'starts_on', 'ends_on']);
            $table->index(['user_id', 'category_id', 'starts_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
