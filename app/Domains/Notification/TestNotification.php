<?php

namespace App\Domains\Notification;

use App\Domains\Notification\DTOs\PushMessage;
use App\Infrastructure\Firebase\Channels\FcmCustomChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(): array
    {
        return ['database', FcmCustomChannel::class];
    }

    public function toArray(): array
    {
        Log::info('[TestNotification] DB notification triggered');

        return [
            'id' => 999,
            'title' => 'Test Notifikasi Moco',
            'message' => 'Ini adalah notifikasi percobaan dari backend. Yey masuk!',
            'code' => 'TEST_PUSH',
        ];
    }

    public function toFcm(): PushMessage
    {
        Log::info('[TestNotification] fcm push notification triggered');
        $notificationId = $this->id ?? Str::uuid()->toString();

        return new PushMessage(
            deviceToken: '',
            title: 'Test Notifikasi Moco',
            body: 'Ini adalah notifikasi percobaan dari backend. Yey masuk!',
            data: [
                'id' => $notificationId,
                'title' => 'Test Notifikasi Moco',
                'message' => 'Ini adalah notifikasi percobaan dari backend. Yey masuk!',

                'isRead' => 'false',
                'readAt' => '',
                'createdAt' => now()->toIso8601String(),

                'payload' => json_encode([
                    'notificationCode' => 'TEST_PUSH',
                    'occurrenceId' => '999',
                ]),
            ],
            image: secure_asset('logo.png'),
        );
    }
}
