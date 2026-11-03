<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentVerificationStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case ManualReview = 'manual_review';
    case Rejected = 'rejected';
    case Suspicious = 'suspicious';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending Verification',
            self::Approved => 'AI Verified & Approved',
            self::ManualReview => 'Manual Review Required',
            self::Rejected => 'Rejected',
            self::Suspicious => 'Suspicious / Flagged',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-badge',
            self::ManualReview => 'heroicon-o-question-mark-circle',
            self::Rejected => 'heroicon-o-x-circle',
            self::Suspicious => 'heroicon-o-shield-exclamation',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::ManualReview => 'info',
            self::Rejected => 'danger',
            self::Suspicious => 'danger',
        };
    }
}
