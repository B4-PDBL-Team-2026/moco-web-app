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
            $table->decimal('amount', 15, 2);
            $table->string('cycle_type'); // weekly | monthly
            $table->unsignedTinyInteger('due_day'); // monthly: 1-31, weekly: 1-7
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'cycle_type', 'is_active']);
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
