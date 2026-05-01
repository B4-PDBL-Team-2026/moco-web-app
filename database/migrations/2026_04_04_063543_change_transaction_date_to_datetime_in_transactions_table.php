<?php

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Transaction\Models\Transaction;
use Carbon\CarbonImmutable;
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
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('transaction_date', 'transaction_at');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dateTime('transaction_at')->change();
        });

        Transaction::chunkById(500, function ($transactions) {
            foreach ($transactions as $transaction) {

                $userTimezone = UserBudgetSetting::where('user_id', $transaction->user_id)->value('timezone') ?? 'UTC';

                $dateString = CarbonImmutable::parse($transaction->transaction_date)->format('Y-m-d');

                $safeLocalTime = CarbonImmutable::createFromFormat(
                    'Y-m-d H:i:s',
                    $dateString.' 12:00:00',
                    $userTimezone
                );

                $transaction->update([
                    'transaction_date' => $safeLocalTime->utc()->toDateTimeString(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('transaction_date')->change();
        });
    }
};
