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
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('expense');
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('USD');
            $table->foreignId('category_id')->constrained();
            $table->text('note')->nullable();
            $table->string('nature', 10)->nullable();
            $table->string('frequency', 10);
            $table->unsignedSmallInteger('interval')->default(1);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('next_run_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'next_run_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
