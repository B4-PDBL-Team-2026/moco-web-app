<?php

use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Notifications\FixedCostOccurrenceNotification;
use App\Domains\Notification\DTOs\PushMessage;
use App\Domains\Notification\Enums\NotificationCode;
use App\Infrastructure\Firebase\Channels\FcmCustomChannel;

it('defines correct delivery channels', function () {
    $occurrence = FixedCostOccurrence::factory()->make();
    $notification = new FixedCostOccurrenceNotification($occurrence);

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

    $notification = new FixedCostOccurrenceNotification($occurrence);
    $arrayData = $notification->toArray();
    $formattedNumber = Number::currency($occurrence->amount, 'IDR', 'id');
    expect($arrayData)->toMatchArray([
        'id' => 99,
        'title' => 'Waktunya bayar '.$occurrence->name.' nih!',
        'message' => 'Tagihan sebesar '.$formattedNumber.' udah mau jatuh tempo, jangan sampai lewat ya!',
        'code' => NotificationCode::FIXED_COST_REMINDER->value,
    ]);
});

it('formats the fcm push message into a valid DTO', function () {
    $occurrence = FixedCostOccurrence::factory()->make([
        'id' => 88,
        'name' => 'Uang Kos',
        'amount' => 2000000,
    ]);

    $dummyId = Str::uuid()->toString();

    $notification = new FixedCostOccurrenceNotification($occurrence);
    $notification->id = $dummyId;
    $pushData = $notification->toFcm();
    $formattedNumber = Number::currency($occurrence->amount, 'IDR', 'id');

    expect($pushData)->toBeInstanceOf(PushMessage::class)
        ->and($pushData->deviceToken)->toBeEmpty()
        ->and($pushData->title)->toBe('Waktunya bayar '.$occurrence->name.' nih!')
        ->and($pushData->body)->toBe('Tagihan sebesar '.$formattedNumber.' udah mau jatuh tempo, jangan sampai lewat ya!')
        ->and($pushData->data['id'])->toBe($dummyId)
        ->and($pushData->data['isRead'])->toBe('false');
});
