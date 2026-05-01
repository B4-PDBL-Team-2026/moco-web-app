<?php

namespace App\Console\Commands;

use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Notifications\FixedCostReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendFixedCostReminders extends Command
{
    protected $signature = 'fixed-cost:remind';

    public function handle(): void
    {
        $items = FixedCostOccurrence::with(['user', 'template'])
            ->where('due_date', '<=', Carbon::today())
            ->whereIn('status', ['pending', 'overdue'])
            ->whereNull('paid_at')
            ->whereNull('voided_at')
            ->get();

        foreach ($items as $item) {
            $item->template->user->notify(new FixedCostReminder($item));
        }

        $this->info('Fixed cost notifications sent successfully');
    }
}
