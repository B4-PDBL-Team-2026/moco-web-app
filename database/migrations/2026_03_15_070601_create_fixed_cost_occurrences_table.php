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
        Schema::create('fixed_cost_occurrences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fixed_cost_template_id')
                ->constrained('fixed_cost_templates')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('cycle_key', 20);  // monthly: 2026-01 (YYYY-MM); weekly: 2026-W12 (YYYY-WWW)
            $table->string('cycle_type'); // monthly | weekly

            $table->date('due_date');
            $table->string('status'); // pending | void | paid | overdue
            $table->decimal('amount', 15);

            $table->string('name', 255);
            $table->string('category_type');
            $table->unsignedBigInteger('category_id');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->unique(['fixed_cost_template_id', 'cycle_key']);
            $table->index(['user_id', 'cycle_key', 'status']);
            $table->index(['user_id', 'due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_cost_occurences');
    }
};
