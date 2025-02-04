<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Space;

class RenewApplication extends Mailable
{
    use Queueable, SerializesModels;

    public $space;

    /**
     * Create a new message instance.
     */
    public function __construct(Space $space)
    {
        $this->space = $space;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Space Lease Renewal Application - ' . $this->space->concourse->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.renew-application',
            with: [
                'space' => $this->space,
                'tenant' => $this->space->user,
                'concourse' => $this->space->concourse,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
