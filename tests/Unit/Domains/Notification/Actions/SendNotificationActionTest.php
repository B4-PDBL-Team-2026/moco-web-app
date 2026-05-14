<?php

use App\Domains\Notification\Actions\SendNotificationAction;
use App\Domains\Notification\Contracts\PushNotification;
use App\Domains\Notification\DTOs\PushMessage;
use Mockery\MockInterface;

it('successfully constructs and sends a push message payload', function () {
    $deviceToken = 'token_fcm_123_abc';
    $title = 'Tagihan Kos';
    $body = 'Jangan lupa bayar kos bulan ini!';
    $data = ['occurrenceId' => '99', 'type' => 'REMINDER'];

    // mock PushNotification
    $mockPushNotification = Mockery::mock(PushNotification::class, function (MockInterface $mock) use ($deviceToken, $title, $body, $data) {
        $mock->shouldReceive('send')
            ->once()
            ->withArgs(function ($message) use ($deviceToken, $title, $body, $data) {
                return $message instanceof PushMessage
                    && $message->deviceToken === $deviceToken
                    && $message->title === $title
                    && $message->body === $body
                    && $message->data === $data;
            });
    });

    $action = new SendNotificationAction($mockPushNotification);

    $action->execute($deviceToken, $title, $body, $data);
});
