<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaseContractMail extends Mailable
{
    use Queueable, SerializesModels;

    public $owner;
    public $tenantUser;
    public $space;
    public $application;
    public $ownerAddress;
    public $tenantAddress;
    public $businessName;

    /**
     * Create a new message instance.
     */
    public function __construct($owner, $tenantUser, $space, $application, $ownerAddress, $tenantAddress, $businessName)
    {
        $this->owner = $owner;
        $this->tenantUser = $tenantUser;
        $this->space = $space;
        $this->application = $application;
        $this->ownerAddress = $ownerAddress;
        $this->tenantAddress = $tenantAddress;
        $this->businessName = $businessName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Lease Contract',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.lease-contract',
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

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->view('emails.lease-contract')
                    ->with([
                        'owner' => $this->owner,
                        'tenantUser' => $this->tenantUser,
                        'space' => $this->space,
                        'application' => $this->application,
                        'ownerAddress' => $this->ownerAddress,
                        'tenantAddress' => $this->tenantAddress,
                        'businessName' => $this->businessName
                    ]);
    }
}
