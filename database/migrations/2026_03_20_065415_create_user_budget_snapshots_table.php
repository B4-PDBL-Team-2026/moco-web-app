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
        Schema::create('user_budget_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->decimal('reserved_cost', 15, 2)->default(0);
            $table->decimal('daily_allowance', 15, 2)->default(0);
            $table->decimal('actual_daily_allowance', 15, 2)->default(0);
            $table->string('current_cycle_key', 20); // monthly: 2026-01 (YYYY-MM); weekly: 2026-W12 (YYYY-WWW)
            $table->date('cycle_start_date');
            $table->date('cycle_end_date');
            $table->unsignedInteger('remaining_days');
            $table->timestamp('recalculated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_budget_statuses');
    }
};
