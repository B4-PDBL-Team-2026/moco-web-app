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
        Schema::create('fixed_cost_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->numericMorphs('category');
            $table->string('deduction_type');
            $table->decimal('amount', 15, 2);
            $table->string('cycle_type');  // monthly: 2026-01 (YYYY-MM); weekly: 2026-W12 (YYYY-WWW)
            $table->unsignedTinyInteger('due_day');
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
