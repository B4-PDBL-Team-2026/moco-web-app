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
        Schema::create('fixed_costs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('deduction_type');
            $table->decimal('amount', 15, 2);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
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
