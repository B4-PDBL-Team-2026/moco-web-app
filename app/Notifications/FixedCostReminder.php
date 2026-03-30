<?php

namespace App\Notifications;

use App\Models\FixedCostOccurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FixedCostReminder extends Notification
{
    use Queueable;

    public $occurrence;

    public function __construct(FixedCostOccurrence $occurrence)
    {
        $this->occurrence = $occurrence;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
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

    public function toArray($notifiable): array
    {
        return [
            'occurrence_id' => $this->occurrence->id,
            'name' => $this->occurrence->template->name,
            'amount' => $this->occurrence->amount,
            'due_date' => $this->occurrence->due_date,
        ];
    }
}
