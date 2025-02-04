<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Space;
use App\Models\User;
use App\Models\Payment;

class PaymentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $space;
    public $user;
    public $payment;
    public $totalPenalty;
    public $dueDate;

    /**
     * Create a new message instance.
     */
    public function __construct(Space $space, User $user, Payment $payment)
    {
        $this->space = $space;
        $this->user = $user;
        $this->payment = $payment;
        $this->totalPenalty = $payment->penalty;
        $this->dueDate = $payment->due_date;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmation',
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
