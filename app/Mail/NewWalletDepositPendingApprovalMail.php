<?php

namespace App\Mail;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewWalletDepositPendingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public float $totalPayableBdt;

    public function __construct(
        public WalletTransaction $transaction,
        public string $approveUrl,
        public string $rejectUrl,
    ) {
        $this->totalPayableBdt = (float) $this->transaction->amount;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Wallet Deposit Pending Approval - '.number_format($this->totalPayableBdt, 2).' BDT',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.wallet.pending-approval',
            with: [
                'transaction' => $this->transaction,
                'approveUrl' => $this->approveUrl,
                'rejectUrl' => $this->rejectUrl,
                'totalPayableBdt' => $this->totalPayableBdt,
            ],
        );
    }
}
