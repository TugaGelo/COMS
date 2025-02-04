<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentBillMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $month,
        public float $rentAmount,
        public float $totalAmount,
        public string $dueDate,
        public int $penalty
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Rent Bill for {$this->month}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rent-bill',
        );
    }
}
