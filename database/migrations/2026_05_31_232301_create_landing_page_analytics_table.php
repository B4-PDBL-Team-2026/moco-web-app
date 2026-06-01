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
        Schema::create('landing_page_analytics', function (Blueprint $table) {
            $table->id();

            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->boolean('reached_scroll_depth')->default(false);
            $table->date('visited_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_analytics');
    }
};
