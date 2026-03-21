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
            $table->numericMorphs('category');
            $table->foreignId('fixed_cost_occurrence_id')->nullable()->unique()->constrained()->nullOnDelete();

            $table->string('type'); // income, expense
            $table->string('source')->default('manual'); // manual, opening_balance, fixed_cost_payment, adjustment
            $table->string('name', 255);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->timestamp('effective_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type', 'transaction_date']);
            $table->index(['user_id', 'fixed_cost_occurrence_id']);
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
