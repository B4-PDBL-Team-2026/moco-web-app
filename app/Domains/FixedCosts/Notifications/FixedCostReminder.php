<?php

namespace App\Domains\FixedCosts\Notifications;

use App\Domains\Notification\DTOs\PushMessage;
use App\Infrastructure\Firebase\Channels\FcmCustomChannel;
use App\Models\FixedCostOccurrence;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixedCostReminder extends Notification
{
    use Queueable;

    public FixedCostOccurrence $occurrence;

    public function __construct(FixedCostOccurrence $occurrence)
    {
        $this->occurrence = $occurrence;
    }

    public function via(): array
    {
        return ['mail', 'database', FcmCustomChannel::class];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $name = $this->occurrence->template->name;
        $amount = number_format($this->occurrence->amount, 0, ',', '.');

        return (new MailMessage)
            ->subject("Pengingat Pembayaran: {$name}")

            ->line("Halo {$notifiable->name}")
            ->line("Pembayaran {$name} sebesar {$amount} akan segera jatuh tempo.")
            ->line('Pastikan saldo kamu cukup untuk menghindari keterlambatan.')
            ->action('Lihat Tagihan', url('/dashboard'))
            ->line('Terima kasih telah menggunakan Moco App!');
    }

    public function toFcm(): PushMessage
    {
        $name = $this->occurrence->name;
        $amount = number_format($this->occurrence->amount, 0, ',', '.');

        return new PushMessage(
            deviceToken: '',
            title: "Pengingat Pembayaran: {$name}",
            body: "Tagihan sebesar {$amount} akan segera jatuh tempo.",
            data: ['occurrence_id' => (string) $this->occurrence->id]
        );
    }
}
