<?php

use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Notifications\FixedCostReminder;
use App\Domains\Notification\DTOs\PushMessage;
use App\Domains\Notification\Enums\NotificationCode;
use App\Infrastructure\Firebase\Channels\FcmCustomChannel;

it('defines correct delivery channels', function () {
    $occurrence = FixedCostOccurrence::factory()->make();
    $notification = new FixedCostReminder($occurrence);

    $channels = $notification->via();

    expect($channels)
        ->toHaveCount(2)
        ->toContain('database', FcmCustomChannel::class);
});

it('formats the database array correctly for In-App Notification', function () {
    $occurrence = FixedCostOccurrence::factory()->make([
        'id' => 99,
        'name' => 'Cicilan Motor',
        'amount' => 1500000,
    ]);

    $notification = new FixedCostReminder($occurrence);
    $arrayData = $notification->toArray();
    $formattedNumber = Number::currency($occurrence->amount, 'IDR', 'id');
    expect($arrayData)->toMatchArray([
        'id' => 99,
        'title' => 'Pengingat Pembayaran: Cicilan Motor',
        'message' => 'Tagihan sebesar '.$formattedNumber.' akan segera jatuh tempo.',
        'code' => NotificationCode::FIXED_COST_REMINDER->value,
    ]);
});

it('formats the fcm push message into a valid DTO', function () {
    $occurrence = FixedCostOccurrence::factory()->make([
        'id' => 88,
        'name' => 'Uang Kos',
        'amount' => 2000000,
    ]);

    $notification = new FixedCostReminder($occurrence);
    $pushData = $notification->toFcm();

    expect($pushData)->toBeInstanceOf(PushMessage::class)
        ->and($pushData->deviceToken)->toBeEmpty()
        ->and($pushData->title)->toBe('Pengingat Pembayaran: Uang Kos')
        ->and($pushData->body)->toBe('Tagihan sebesar 2.000.000 akan segera jatuh tempo.')
        ->and($pushData->data)->toMatchArray(['occurrence_id' => '88']);
});
