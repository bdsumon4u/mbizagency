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

    public float $processingFee;

    public float $totalPayableBdt;

    public function __construct(
        public WalletTransaction $transaction,
        public string $approveUrl,
        public string $rejectUrl,
    ) {
        $paymentMethod = $this->transaction->paymentMethod ?: $this->transaction->paymentMethod()->first();
        $feePercent = $paymentMethod ? (float) $paymentMethod->processing_fee_percent : 0.0;
        $this->processingFee = round(((float) $this->transaction->amount) * ($feePercent / 100), 2);
        $this->totalPayableBdt = ((float) $this->transaction->amount) + $this->processingFee;
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
                'processingFee' => $this->processingFee,
                'totalPayableBdt' => $this->totalPayableBdt,
            ],
        );
    }
}
