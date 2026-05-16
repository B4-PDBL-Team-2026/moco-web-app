<?php

namespace App\Domains\FixedCost\Notifications;

use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\Notification\DTOs\PushMessage;
use App\Domains\Notification\Enums\NotificationCode;
use App\Infrastructure\Firebase\Channels\FcmCustomChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Number;

class FixedCostOccurrenceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public FixedCostOccurrence $occurrence;

    public function __construct(FixedCostOccurrence $occurrence)
    {
        $this->occurrence = $occurrence;
    }

    public function via(): array
    {
        return ['database', FcmCustomChannel::class];
    }

    public function toArray(): array
    {
        $formattedAmount = Number::currency($this->occurrence->amount, 'IDR', 'id');

        return [
            'id' => $this->occurrence->id,
            'title' => 'Waktunya bayar '.$this->occurrence->name.' nih!',
            'message' => 'Tagihan sebesar '.$formattedAmount.' udah mau jatuh tempo, jangan sampai lewat ya!',
            'code' => NotificationCode::FIXED_COST_REMINDER->value,
        ];
    }

    public function toFcm(): PushMessage
    {
        $name = $this->occurrence->name;
        $formattedAmount = Number::currency($this->occurrence->amount, 'IDR', 'id');

        return new PushMessage(
            deviceToken: '',
            title: 'Waktunya bayar '.$this->occurrence->name.' nih!',
            body: 'Tagihan sebesar '.$formattedAmount.' udah mau jatuh tempo, jangan sampai lewat ya!',
            data: [
                'id' => $this->id,
                'title' => 'Waktunya bayar '.$this->occurrence->name.' nih!',
                'message' => 'Tagihan sebesar '.$formattedAmount.' udah mau jatuh tempo, jangan sampai lewat ya!',
                'isRead' => 'false',
                'readAt' => '',
                'createdAt' => now()->toIso8601String(),
                'payload' => json_encode([
                    'occurrenceId' => (string) $this->occurrence->id,
                    'notificationCode' => NotificationCode::FIXED_COST_REMINDER->value,
                ]),
            ],
            image: secure_asset('logo.png'),
        );
    }
}
