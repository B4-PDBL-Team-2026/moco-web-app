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
        Schema::create('user_budget_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cycle_type'); // weekly or monthly
            $table->decimal('ceiling_limit', 15, 2)->default(0);
            $table->decimal('flooring_limit', 15, 2)->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->string('timezone');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_budget_settings');
    }
};
