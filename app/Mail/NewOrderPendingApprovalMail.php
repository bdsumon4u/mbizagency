<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $approveUrl,
        public string $rejectUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Order Pending Approval',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.pending-approval',
            with: [
                'order' => $this->order,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
            ],
        );
    }
}
