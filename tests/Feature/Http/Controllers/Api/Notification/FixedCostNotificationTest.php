<?php

namespace Tests\Feature\Http\Controllers\Api\Notification;

use Tests\TestCase;
use App\Models\User;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use Illuminate\Support\Facades\Notification;
use App\Notifications\FixedCostReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class FixedCostNotificationTest extends TestCase
{
    use RefreshDatabase; 

    public function test_notification_is_sent_on_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $template = FixedCostTemplate::create([
            'user_id'       => $user->id,
            'name'          => 'Motorcycle Installment',
            'amount'        => 1500000,
            'nominal'       => 1500000,
            'due_day'      => 25, 
            'cycle_type'    => 'monthly', 
            'category_type' => 'expense',
            'category_id'   => 1,
        ]);

        FixedCostOccurrence::create([
            'fixed_cost_template_id' => $template->id,
            'user_id'       => $user->id,
            'name'          => 'Motorcycle Installment - March',
            'amount'        => 1500000,
            'due_date'      => Carbon::today(), 
            'status'        => 'pending',
            'cycle_key'     => '2026-03',
            'cycle_type'    => 'monthly',
            'category_type' => 'expense',
            'category_id'   => 1
        ]);

        $this->artisan('fixed-cost:remind');

        Notification::assertSentTo($user, FixedCostReminder::class);
    }
}