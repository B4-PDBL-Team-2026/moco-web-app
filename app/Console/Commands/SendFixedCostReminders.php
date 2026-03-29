<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FixedCostOccurrence;
use App\Notifications\FixedCostReminder;
use Carbon\Carbon;

class SendFixedCostReminders extends Command
{

    protected $signature = 'fixed-cost:remind';

    public function handle()
    {
        $items = FixedCostOccurrence::where('due_date', '<=', Carbon::today())
            ->whereIn('status', ['pending', 'overdue'])
            ->whereNull('paid_at')
            ->whereNull('voided_at')
            ->get();

        foreach ($items as $item) {
            $item->template->user->notify(new FixedCostReminder($item));
        }

        $this->info('Fixed fee notification check completed');
    }
}
