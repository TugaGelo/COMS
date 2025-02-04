<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Ticket;

class TicketReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $admin;
    public $tenant;
    public $ticket;
    public $spaceName;
    public $concourseName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $admin, User $tenant, Ticket $ticket, $spaceName, $concourseName)
    {
        $this->admin = $admin;
        $this->tenant = $tenant;
        $this->ticket = $ticket;
        $this->spaceName = $spaceName;
        $this->concourseName = $concourseName;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Ticket Report Submitted')
                    ->view('emails.ticket_report')
                    ->with([
                        'adminName' => $this->admin->name,
                        'tenantName' => $this->tenant->name,
                        'spaceName' => $this->spaceName,
                        'concourseName' => $this->concourseName,
                        'concernType' => $this->ticket->concern_type,
                        'description' => $this->ticket->description,
                        'ticketNumber' => $this->ticket->id,
                        'submittedDate' => $this->ticket->created_at->format('Y-m-d H:i:s')
                    ]);
    }
}
