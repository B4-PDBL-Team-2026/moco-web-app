<?php

namespace App\Console\Commands;

use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[Signature('fixed-cost:mark-overdue')]
#[Description('Mark pending fixed cost occurrences as overdue based on user timezone.')]
class MarkOverdueFixedCostOccurrences extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting mark overdue fixed cost');

        $shouldOverdueOccurrences = [];

        FixedCostOccurrence::with('user.budgetSetting')
            ->where('status', '=', FixedCostOccurenceStatus::PENDING)
            ->whereNull('paid_at')
            ->whereNull('voided_at')
            ->chunkById(500, function ($occurrences) use (&$shouldOverdueOccurrences) {
                foreach ($occurrences as $occurrence) {
                    $timezone = $occurrence->user->budgetSetting->timezone ?? 'UTC';

                    $userToday = CarbonImmutable::today($timezone);

                    $dueDate = CarbonImmutable::parse($occurrence->due_date, $timezone);

                    if ($dueDate->lt($userToday)) {
                        $shouldOverdueOccurrences[] = $occurrence;
                    }
                }
            });

        if (empty($shouldOverdueOccurrences)) {
            $this->info('No occurrences neet to be updated.');

            return CommandAlias::SUCCESS;
        }

        $updatedCount = FixedCostOccurrence::query()
            ->whereIn('id', $shouldOverdueOccurrences)
            ->update([
                'status' => FixedCostOccurenceStatus::OVERDUE,
                'updated_at' => now(),
            ]);

        $this->info('Marked '.$updatedCount.' occurrences as overdue.');

        return CommandAlias::SUCCESS;
    }
}
