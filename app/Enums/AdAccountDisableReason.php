<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum AdAccountDisableReason: int implements HasDescription, HasLabel
{
    case NONE = 0;
    case ADS_INTEGRITY_POLICY = 1;
    case ADS_IP_REVIEW = 2;
    case RISK_PAYMENT = 3;
    case GRAY_ACCOUNT_SHUT_DOWN = 4;
    case ADS_AFC_REVIEW = 5;
    case BUSINESS_INTEGRITY_RAR = 6;
    case PERMANENT_CLOSE = 7;
    case UNUSED_RESELLER_ACCOUNT = 8;
    case UNUSED_ACCOUNT = 9;
    case UMBRELLA_AD_ACCOUNT = 10;
    case BUSINESS_MANAGER_INTEGRITY_POLICY = 11;
    case MISREPRESENTED_AD_ACCOUNT = 12;
    case AOAB_DESHARE_LEGAL_ENTITY = 13;
    case CTX_THREAD_REVIEW = 14;
    case COMPROMISED_AD_ACCOUNT = 15;

    public function getLabel(): string
    {
        return match ($this) {
            self::NONE => 'none',
            self::ADS_INTEGRITY_POLICY => 'ads_integrity_policy',
            self::ADS_IP_REVIEW => 'ads_ip_review',
            self::RISK_PAYMENT => 'risk_payment',
            self::GRAY_ACCOUNT_SHUT_DOWN => 'gray_account_shut_down',
            self::ADS_AFC_REVIEW => 'ads_afc_review',
            self::BUSINESS_INTEGRITY_RAR => 'business_integrity_rar',
            self::PERMANENT_CLOSE => 'permanent_close',
            self::UNUSED_RESELLER_ACCOUNT => 'unused_reseller_account',
            self::UNUSED_ACCOUNT => 'unused_account',
            self::UMBRELLA_AD_ACCOUNT => 'umbrella_ad_account',
            self::BUSINESS_MANAGER_INTEGRITY_POLICY => 'business_manager_integrity_policy',
            self::MISREPRESENTED_AD_ACCOUNT => 'misrepresented_ad_account',
            self::AOAB_DESHARE_LEGAL_ENTITY => 'aoab_deshare_legal_entity',
            self::CTX_THREAD_REVIEW => 'ctx_thread_review',
            self::COMPROMISED_AD_ACCOUNT => 'compromised_ad_account',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::ADS_INTEGRITY_POLICY => 'Ads Integrity Policy',
            self::ADS_IP_REVIEW => 'Ads IP Review',
            self::RISK_PAYMENT => 'Risk Payment',
            self::GRAY_ACCOUNT_SHUT_DOWN => 'Gray Account Shut Down',
            self::ADS_AFC_REVIEW => 'Ads AFC Review',
            self::BUSINESS_INTEGRITY_RAR => 'Business Integrity RAR',
            self::PERMANENT_CLOSE => 'Permanent Close',
            self::UNUSED_RESELLER_ACCOUNT => 'Unused Reseller Account',
            self::UNUSED_ACCOUNT => 'Unused Account',
            self::UMBRELLA_AD_ACCOUNT => 'Umbrella Ad Account',
            self::BUSINESS_MANAGER_INTEGRITY_POLICY => 'Business Manager Integrity Policy',
            self::MISREPRESENTED_AD_ACCOUNT => 'Misrepresented Ad Account',
            self::AOAB_DESHARE_LEGAL_ENTITY => 'AOAB Deshare Legal Entity',
            self::CTX_THREAD_REVIEW => 'CTX Thread Review',
            self::COMPROMISED_AD_ACCOUNT => 'Compromised Ad Account',
        };
    }
}
