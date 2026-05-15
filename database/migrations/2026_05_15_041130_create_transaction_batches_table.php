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
        Schema::create('transaction_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('type');
            $table->dateTime('transaction_at');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'transaction_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('transaction_batch_id')
                ->nullable()
                ->after('fixed_cost_occurrence_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['transaction_batch_id']);
            $table->dropColumn('transaction_batch_id');
        });

        Schema::dropIfExists('transaction_batches');
    }
};
