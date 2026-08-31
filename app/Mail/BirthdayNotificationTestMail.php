<?php

namespace App\Mail;

use App\Filament\Resources\BirthdayNotifications\Support\BirthdayNotificationPresentation;
use App\Models\BirthdayNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BirthdayNotificationTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public BirthdayNotification $notification,
        public User $sentBy,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Prueba] '.$this->notification->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.birthday-notification-test',
            with: [
                'imageUrl' => BirthdayNotificationPresentation::imageUrl($this->notification),
                'sentByName' => $this->sentBy->name,
            ],
        );
    }
}
