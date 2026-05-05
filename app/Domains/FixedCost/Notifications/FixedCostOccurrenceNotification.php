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
            'title' => 'Pengingat Pembayaran: '.$this->occurrence->name,
            'message' => 'Tagihan sebesar '.$formattedAmount.' akan segera jatuh tempo.',
            'code' => NotificationCode::FIXED_COST_REMINDER->value,
        ];
    }

    public function toFcm(): PushMessage
    {
        $name = $this->occurrence->name;
        $formattedAmount = Number::currency($this->occurrence->amount, 'IDR', 'id');

        return new PushMessage(
            deviceToken: '',
            title: "Pengingat Pembayaran: {$name}",
            body: "Tagihan sebesar {$formattedAmount} akan segera jatuh tempo.",
            data: [
                'id' => $this->id,
                'title' => 'Pengingat Pembayaran: '.$this->occurrence->name,
                'message' => 'Tagihan sebesar '.$formattedAmount.' akan segera jatuh tempo.',
                'isRead' => 'false',
                'readAt' => '',
                'createdAt' => now()->toIso8601String(),
                'payload' => json_encode([
                    'occurrence_id' => (string) $this->occurrence->id,
                    'notificationCode' => NotificationCode::FIXED_COST_REMINDER->value,
                ]),
            ],
        );
    }
}
