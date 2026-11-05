<?php

namespace App\Services;

use Carbon\Carbon;

class SmsParserService
{
    /**
     * @return array{
     *     provider: ?string,
     *     sender_title: ?string,
     *     trx_id: ?string,
     *     amount: ?float,
     *     balance: ?float,
     *     fee: float,
     *     sender_account: ?string,
     *     receiver_account: ?string,
     *     reference: ?string,
     *     txn_type: ?string,
     *     transaction_time: ?Carbon,
     *     clean_message: string
     * }
     */
    public function parse(string $rawMessage): array
    {
        $senderTitle = $this->extractSenderTitle($rawMessage);
        $cleanMessage = $this->cleanForwarderEnvelope($rawMessage);

        $provider = $this->detectProvider($senderTitle, $cleanMessage);
        $trxId = $this->extractTrxId($cleanMessage);
        $amount = $this->extractAmount($cleanMessage);
        $balance = $this->extractBalance($cleanMessage);
        $fee = $this->extractFee($cleanMessage);
        $senderAccount = $this->extractSenderAccount($cleanMessage);
        $receiverAccount = $this->extractReceiverAccount($cleanMessage);
        $reference = $this->extractReference($cleanMessage);
        $txnType = $this->extractTxnType($cleanMessage);
        $transactionTime = $this->extractTransactionTime($cleanMessage);

        return [
            'provider' => $provider,
            'sender_title' => $senderTitle,
            'trx_id' => $trxId,
            'amount' => $amount,
            'balance' => $balance,
            'fee' => $fee,
            'sender_account' => $senderAccount,
            'receiver_account' => $receiverAccount,
            'reference' => $reference,
            'txn_type' => $txnType,
            'transaction_time' => $transactionTime,
            'clean_message' => $cleanMessage,
        ];
    }

    private function extractSenderTitle(string $text): ?string
    {
        if (preg_match('/(?:From|Sender|Source):\s*([a-zA-Z0-9_\-\+]+)/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function cleanForwarderEnvelope(string $text): string
    {
        if (preg_match('/(?:Message|SMS|Body):\s*(.*)/is', $text, $matches)) {
            return trim($matches[1]);
        }

        return trim($text);
    }

    public function detectProvider(?string $senderTitle, string $message): string
    {
        $haystack = mb_strtolower(($senderTitle ?? '').' '.$message);

        // Eastern Bank (EBL)
        if (str_contains($haystack, 'ebl') || str_contains($haystack, '16230') || str_contains($haystack, 'eastern bank')) {
            return 'ebl';
        }

        // BRAC Bank (Astha)
        if (str_contains($haystack, 'brac') || str_contains($haystack, '16221') || str_contains($haystack, 'astha')) {
            return 'brac_bank';
        }

        // City Bank (Citytouch)
        if (str_contains($haystack, 'citytouch') || str_contains($haystack, 'city bank')) {
            return 'city_bank';
        }

        // Dutch-Bangla Bank (DBBL / NexusPay)
        if (
            str_contains($haystack, 'nexuspay') ||
            str_contains($haystack, 'dutch-bangla') ||
            str_contains($haystack, 'dbbl') ||
            str_contains($haystack, 'nexus') ||
            str_contains($haystack, 'crm/cdm')
        ) {
            return 'dbbl';
        }

        if (str_contains($haystack, 'bkash')) {
            return 'bkash';
        }

        if (str_contains($haystack, 'nagad')) {
            return 'nagad';
        }

        if (str_contains($haystack, 'rocket') || str_contains($haystack, '16216') || preg_match('/01[3-9]\d{8}-\d/', $message)) {
            return 'rocket';
        }

        if (str_contains($haystack, 'upay')) {
            return 'upay';
        }

        // Distinct bKash patterns
        if (preg_match('/\bTrxID\b/i', $message) || str_contains($message, 'You have received Tk')) {
            return 'bkash';
        }

        // Distinct Nagad patterns
        if (preg_match('/\bTxnID\b/i', $message) || str_contains($message, 'Money Received: Tk')) {
            return 'nagad';
        }

        if (
            str_contains($haystack, 'bank') ||
            str_contains($haystack, 'credited') ||
            str_contains($haystack, 'npsb') ||
            str_contains($haystack, 'beftn') ||
            str_contains($haystack, 'rtgs') ||
            str_contains($haystack, 'a/c')
        ) {
            return 'bank';
        }

        return 'other';
    }

    public function extractTrxId(string $text): ?string
    {
        $patterns = [
            '/(?:TrxID|Trx\s*Id|Trx\s*#|TxnID|Txn\s*Id|Txn\s*#|Transaction\s*ID)[:\s]*([A-Z0-9]{6,25})/i',
            '/(?:TXN\s*NO\.?|Ref|Reference|Ref\s*No)[:\s]*([A-Z0-9]{6,25})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return strtoupper(trim($matches[1]));
            }
        }

        return null;
    }

    public function extractAmount(string $text): ?float
    {
        // Support comma separated numbers (including South Asian grouping e.g. 6,11,937.85) and unformatted integers (e.g. 38100)
        $num = '(?:\d{1,3}(?:,\d{2,3})+|\d+)(?:\.\d{1,2})?';

        $patterns = [
            // E.g. "credited with BDT 38100 as"
            '/credited\s+with\s+(?:Tk\.?|BDT|৳)?\s*('.$num.')/i',
            // E.g. "by Tk11,875.00" or "by Tk8,000.00" or "with BDT 38100"
            '/(?:by|with)\s*(?:Tk\.?|BDT|৳)\s*('.$num.')/i',
            // E.g. "Tk. 6,400 Deposit"
            '/(?:Tk\.?|BDT|৳)\s*('.$num.')\s+Deposit/i',
            // E.g. "TK 19,500.00 has been credited"
            '/(?:Tk\.?|BDT|৳)\s*('.$num.')\s+has\s+been\s+credited/i',
            // E.g. "credited by BDT 10,000.00"
            '/credited\s+by\s+(?:Tk\.?|BDT|৳)?\s*('.$num.')/i',
            // E.g. "Tk11,875.00"
            '/(?:Tk|BDT|৳)('.$num.')/i',
            // Standard "Tk 1,500.00"
            '/(?:Tk\.?|BDT|৳)\s*('.$num.')/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $rawAmount = str_replace(',', '', $matches[1]);

                return (float) $rawAmount;
            }
        }

        return null;
    }

    public function extractBalance(string $text): ?float
    {
        $num = '(?:\d{1,3}(?:,\d{2,3})+|\d+)(?:\.\d{1,2})?';

        $patterns = [
            // DBBL Closing Balance: "C/B Tk29,886.61" or "C/B Tk9,691.61"
            '/C\/B\s*(?:Tk\.?|BDT|৳)?\s*('.$num.')/i',
            // City Bank: "Tk. 1,35,318 Balance"
            '/(?:Tk\.?|BDT|৳)\s*('.$num.')\s+Balance/i',
            // EBL: "Balance is BDT 38207.96"
            '/Balance\s+is\s+(?:BDT|Tk\.?|৳)?\s*('.$num.')/i',
            // BRAC: "Your A/C balance is TK 6,11,937.85"
            '/(?:A\/C\s+balance\s+is|Bal:?)\s*(?:BDT|Tk\.?|৳)?\s*('.$num.')/i',
            // General: "Balance Tk 15,250.00"
            '/Balance\s*(?:Tk\.?|BDT|৳)?\s*('.$num.')/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return (float) str_replace(',', '', $matches[1]);
            }
        }

        return null;
    }

    public function extractFee(string $text): float
    {
        if (preg_match('/Fee\s*(?:Tk\.?|BDT)?\s*([0-9]{1,3}(?:,[0-9]{3})*(?:\.[0-9]{1,2})?)/i', $text, $matches)) {
            return (float) str_replace(',', '', $matches[1]);
        }

        return 0.0;
    }

    public function extractSenderAccount(string $text): ?string
    {
        if (preg_match('/from\s+([0-9\+\*\-]{8,20})/i', $text, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function extractReceiverAccount(string $text): ?string
    {
        $patterns = [
            // DBBL / Brac / EBL: "A/C ***2219", "A/C# 20539**0001", "AC 107***337", "A/C: 1503***4001"
            '/(?:A\/C#?|AC)\s*:?\s*([0-9\*\-xX]{4,25})/i',
            // General: "to your A/C# 20539**0001"
            '/to\s+(?:your\s+)?A\/C#?\s*([0-9\*\-xX]{4,25})/i',
            // Rocket: "A/C: 01812345678-0"
            '/A\/C:\s*([0-9\*\-]{4,20})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    public function extractTxnType(string $text): ?string
    {
        $patterns = [
            // DBBL: "credited (Fund Transfer) by"
            '/credited\s*\(([^)]+)\)/i',
            // DBBL: "credited CRM/CDM Cash Deposit On-Us by"
            '/credited\s+([A-Za-z0-9\s\/\-_]+?)\s+by/i',
            // EBL: "as EBL Account Transfer on"
            '/as\s+([A-Za-z0-9\s\/\-_]+?)\s+on/i',
            // City Bank: "CITYTOUCH TXN"
            '/(CITYTOUCH\s+TXN)/i',
            // Cash Deposit
            '/(CASH\s+DEPOSIT)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    public function extractReference(string $text): ?string
    {
        if (preg_match('/Ref(?:\s+|\:)([a-zA-Z0-9_\-]+)/i', $text, $matches)) {
            $ref = trim($matches[1]);
            if (strcasecmp($ref, 'Fee') !== 0 && strcasecmp($ref, 'Balance') !== 0) {
                return $ref;
            }
        }

        return null;
    }

    public function extractTransactionTime(string $text): ?Carbon
    {
        $timezone = date_default_timezone_get();

        $datePatterns = [
            // E.g. "on 29-OCT-26 05:20 PM" or "on 29-10-2026 12:20:10 PM"
            '/(?:on|at|Date:?)\s*([0-9]{2}-[a-zA-Z]{3}-[0-9]{2,4}\s+[0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?\s*(?:AM|PM)?)/i',
            '/(?:on|at|Date:?)\s*([0-9]{2}-[0-9]{2}-[0-9]{2,4}\s+[0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?\s*(?:AM|PM)?)/i',
            '/(?:on|at|Date:?)\s*([0-9]{2}\/[0-9]{2}\/[0-9]{2,4}\s+[0-9]{1,2}:[0-9]{2}(?::[0-9]{2})?\s*(?:AM|PM)?)/i',
            '/(20[0-9]{2}-[0-9]{2}-[0-9]{2}[T ]+[0-9]{2}:[0-9]{2}(?::[0-9]{2})?(?:Z|[+-][0-9]{2}:?[0-9]{2})?)/i',
            // E.g. "29-Oct-2026"
            '/([0-9]{2}-[a-zA-Z]{3}-[0-9]{2,4})/',
            // E.g. "29-10-26" or "03-11-26"
            '/(?:on|Date:?)\s*([0-9]{2}-[0-9]{2}-[0-9]{2,4})/',
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $rawDate = trim($matches[1]);
                try {
                    if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}\s/', $rawDate)) {
                        foreach (['!d/m/Y H:i:s A', '!d/m/Y H:i A', '!d/m/Y H:i:s', '!d/m/Y H:i'] as $format) {
                            try {
                                return Carbon::createFromFormat($format, $rawDate, $timezone);
                            } catch (\Throwable) {
                                // Try the next supported Bangladesh date/time format.
                            }
                        }
                    }

                    // Handle DD-MM-YY format (e.g. 03-11-26 => 2026-11-03)
                    if (preg_match('/^([0-9]{2})-([0-9]{2})-([0-9]{2})$/', $rawDate, $dmy)) {
                        $date = Carbon::createFromFormat('!d-m-y', $rawDate, $timezone);

                        return $date->setTimeFrom(Carbon::now($timezone));
                    }

                    $hasTime = preg_match('/[0-9]{1,2}:[0-9]{2}/', $rawDate) === 1;
                    $parsedDate = Carbon::parse($rawDate, $timezone);

                    if (! $hasTime) {
                        $parsedDate->setTimeFrom(Carbon::now($timezone));
                    }

                    return $parsedDate;
                } catch (\Throwable) {
                    // continue
                }
            }
        }

        return null;
    }

    /**
     * Check if an account number matches a masked account format (e.g. 1503***4001, ***2219, 20539**0001, 1503XXXXXX001).
     * Supports comparing an unmasked account against a masked account, or two differently masked representations.
     */
    public function matchesMaskedAccount(string $fullAccount, string $maskedAccount): bool
    {
        $acc1 = trim($fullAccount);
        $acc2 = trim($maskedAccount);

        if ($acc1 === '' || $acc2 === '') {
            return false;
        }

        if ($acc1 === $acc2) {
            return true;
        }

        $isMasked1 = (bool) preg_match('/[\*xX]/', $acc1);
        $isMasked2 = (bool) preg_match('/[\*xX]/', $acc2);

        // Case A: Both are masked accounts (e.g. bKash "1503XXXXXX001" vs Bank SMS "1503***4001")
        if ($isMasked1 && $isMasked2) {
            $split1 = preg_split('/[\*xX]+/', $acc1);
            $split2 = preg_split('/[\*xX]+/', $acc2);

            $parts1 = array_values(array_filter(is_array($split1) ? $split1 : [], fn (string $p): bool => $p !== ''));
            $parts2 = array_values(array_filter(is_array($split2) ? $split2 : [], fn (string $p): bool => $p !== ''));

            $hasPrefix1 = ! preg_match('/^[\*xX]/', $acc1);
            $hasPrefix2 = ! preg_match('/^[\*xX]/', $acc2);

            // If both have prefixes, compare prefixes
            if ($hasPrefix1 && $hasPrefix2) {
                $p1 = (string) ($parts1[0] ?? '');
                $p2 = (string) ($parts2[0] ?? '');
                $minPrefixLen = min(strlen($p1), strlen($p2));
                if ($minPrefixLen >= 3 && substr($p1, 0, $minPrefixLen) !== substr($p2, 0, $minPrefixLen)) {
                    return false;
                }
            } elseif ($hasPrefix1 !== $hasPrefix2 && ! empty($parts1) && ! empty($parts2)) {
                // One has a prefix and one does not (e.g. ***2219 vs 1641***2219)
                // We allow matching on suffix
            }

            // Compare suffixes
            $s1 = (string) end($parts1);
            $s2 = (string) end($parts2);

            if ($s1 !== '' && $s2 !== '') {
                $minLen = min(strlen($s1), strlen($s2));

                return substr($s1, -$minLen) === substr($s2, -$minLen);
            }

            return true;
        }

        // Case B: One is unmasked and one is masked
        $unmasked = $isMasked1 ? $acc2 : $acc1;
        $masked = $isMasked1 ? $acc1 : $acc2;

        $cleanUnmasked = (string) preg_replace('/[^0-9]/', '', $unmasked);
        $split = preg_split('/[\*xX]+/', $masked);
        if (! is_array($split)) {
            return false;
        }

        $parts = array_values(array_filter($split, fn (string $p): bool => $p !== ''));

        if (empty($parts)) {
            return true;
        }

        // Only suffix masked, e.g. ***2219
        if (preg_match('/^[\*xX]/', $masked)) {
            $suffix = (string) end($parts);
            if (! str_ends_with($cleanUnmasked, $suffix)) {
                return false;
            }

            if (count($parts) === 1) {
                return true;
            }
        }

        // Prefix and Suffix, e.g. 1503***4001 or 20539**0001
        $prefix = (string) ($parts[0] ?? '');
        $suffix = (string) end($parts);

        return str_starts_with($cleanUnmasked, $prefix) && str_ends_with($cleanUnmasked, $suffix);
    }
}
