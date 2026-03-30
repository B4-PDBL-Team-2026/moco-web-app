<?php

use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\User;
use App\Notifications\FixedCostReminder;

it('formats the notification message correctly based on occurrence data', function () {
    $user = User::factory()->make(['name' => 'Wina Rahmalia']);

    $template = FixedCostTemplate::factory()->make([
        'name' => 'Cicilan Motor',
    ]);

    $occurrence = FixedCostOccurrence::factory()->make([
        'amount' => 1500000,
        'due_date' => now()->format('Y-m-d'),
    ]);

    $occurrence->setRelation('template', $template);

    $notification = new FixedCostReminder($occurrence);
    $mailData = $notification->toMail($user);

    expect($mailData->subject)->toContain('Pengingat Pembayaran: Cicilan Motor')
        ->and($mailData->introLines[0])->toContain('Halo Wina Rahmalia')
        ->and($mailData->introLines[1])->toContain('Pembayaran Cicilan Motor sebesar 1.500.000 akan segera jatuh tempo.');
});

it('defines correct delivery channels', function () {
    $occurrence = FixedCostOccurrence::factory()->make();
    $notification = new FixedCostReminder($occurrence);
    $user = User::factory()->make();

    $channels = $notification->via($user);

    expect($channels)->toContain('mail', 'database');
});
