<?php

namespace App\Mail;

use App\Domains\Feedback\Models\Feedback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Feedback $feedback
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Masukan Anda Telah Kami Terima - MOCO',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.feedback.received',
            with: [
                'feedback' => $this->feedback,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
