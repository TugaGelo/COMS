<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UtilityBillMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $month,
        public ?float $waterConsumption = null,
        public ?float $waterRate = null,
        public ?float $waterBill = null,
        public ?float $electricityConsumption = null,
        public ?float $electricityRate = null,
        public ?float $electricityBill = null,
        public string $dueDate,
        public int $penalty,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Utility Bill for ' . $this->month,
        );
    }

    public function content(): Content
    {
        $subtotal = ($this->waterBill ?? 0) + ($this->electricityBill ?? 0);

        return new Content(
            view: 'emails.utility-bill',
            with: [
                'tenantName' => $this->tenantName,
                'month' => $this->month,
                'waterConsumption' => $this->waterConsumption,
                'waterRate' => $this->waterRate,
                'waterBill' => $this->waterBill,
                'electricityConsumption' => $this->electricityConsumption,
                'electricityRate' => $this->electricityRate,
                'electricityBill' => $this->electricityBill,
                'subtotal' => $subtotal,
                'dueDate' => $this->dueDate,
                'penalty' => $this->penalty,
            ],
        );
    }
}
