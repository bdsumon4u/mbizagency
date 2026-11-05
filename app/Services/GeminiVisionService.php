<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiVisionService
{
    /**
     * @return array{
     *     provider: ?string,
     *     trx_id: ?string,
     *     amount: ?float,
     *     fee: ?float,
     *     total_amount: ?float,
     *     balance: ?float,
     *     txn_type: ?string,
     *     beneficiary_bank: ?string,
     *     sender_account: ?string,
     *     sender_number: ?string,
     *     sender_name: ?string,
     *     receiver_account: ?string,
     *     receiver_number: ?string,
     *     receiver_name: ?string,
     *     reference: ?string,
     *     transaction_time: ?string,
     *     status_in_screenshot: string,
     *     is_suspicious_or_edited: bool,
     *     confidence_score: float,
     *     notes: ?string,
     *     raw_response?: array<string, mixed>
     * }
     */
    public function analyzeScreenshot(string $screenshotPath): array
    {
        $apiKey = config('payment_verification.gemini.api_key');
        $model = config('payment_verification.gemini.model', 'gemini-3.5-flash-lite');
        $baseUrl = config('payment_verification.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');

        if (empty($apiKey)) {
            Log::warning('GEMINI_API_KEY is not configured in .env. Skipping automated AI screenshot extraction.');

            return $this->fallbackResponse('Gemini API key is not configured.');
        }

        $imageDetails = $this->resolveImageData($screenshotPath);
        if (! $imageDetails) {
            return $this->fallbackResponse("Screenshot file not found at: {$screenshotPath}");
        }

        $prompt = <<<'PROMPT'
You are an expert financial verification auditor analyzing transaction confirmation proofs from Bangladesh:
- Mobile Financial Services (bKash, Nagad, Rocket, Upay)
- Internet Banking Apps (City Bank "citytouch", Dutch-Bangla Bank "NexusPay", BRAC Bank "Astha" / আস্থা, Eastern Bank "EBL")
- Physical ATM / CRM / CDM Cash Deposit Printed Slips (e.g. Dutch-Bangla Bank / DBBL CRM receipts)

Inspect the entire image carefully. Extract every visible transaction field; do not omit a field that is clearly printed.
Never infer, invent, or replace a printed value with the current date/time or the image upload/server receipt time.
Preserve account numbers exactly as displayed, including masking characters such as *, x, or X.

Map account labels exactly:
- "From", "From Account", "Sender", or "Source Account" -> sender_account and sender_name.
- "To", "To Account", "Transferred to", "Beneficiary", or "Recipient" -> receiver_account and receiver_name.
- Do not swap sender and receiver, and do not put account numbers only in notes.

Reference mapping for these receipt layouts:
- City Bank / Citytouch "Reference No" is the transaction identifier: set both trx_id and reference to that exact value.
- BRAC Bank / Astha "Reference Number" is the transaction identifier: set both trx_id and reference to that exact value.
- EBL "REF:" is a reference number: set reference, and set trx_id only if a separate transaction ID is explicitly shown.
- For other providers, put explicitly labeled TrxID/TxnID/Transaction ID/TXN NO in trx_id; keep a separately labeled reference in reference.

Provider Determination for MFS to Bank Transfers (bKash to Bank / বিকাশ টু ব্যাংক, Nagad to Bank, Rocket to Bank, etc.):
- When money is sent from an MFS app to a bank account (shows a receiving bank name or logo, such as City Bank / সিটি ব্যাংক, BRAC Bank / ব্র্যাক ব্যাংক, DBBL / ডাচ-বাংলা ব্যাংক, Eastern Bank / EBL / ইস্টার্ন ব্যাংক):
  * The payment is received by the destination bank, and the confirmation SMS will come from that receiving bank.
  * Therefore, "provider" MUST be set to the receiving/beneficiary bank:
    - "city_bank" if the receiving bank is City Bank / সিটি ব্যাংক
    - "brac_bank" if the receiving bank is BRAC Bank / ব্র্যাক ব্যাংক
    - "dbbl" if the receiving bank is Dutch-Bangla Bank / DBBL / ডাচ-বাংলা ব্যাংক
    - "ebl" if the receiving bank is Eastern Bank / EBL / ইস্টার্ন ব্যাংক
  * Put the receiving bank name in "beneficiary_bank" (e.g. "City Bank" or "সিটি ব্যাংক").
  * Put the MFS transfer description in "txn_type" (e.g. "বিকাশ টু ব্যাংক-এ টাকা পাঠানো").
  * Put the net transfer amount in "amount" (e.g. 7000.00), any fee in "fee" (e.g. 87.50), and total in "total_amount" (e.g. 7087.50).

Rules for determining status_in_screenshot:
- Bangladeshi internet banking receipts (City Bank "citytouch", BRAC Bank "Astha", DBBL "NexusPay", EBL), MFS apps (bKash, Nagad, Rocket, Upay), and printed ATM/CRM deposit slips are generated AFTER a transaction has occurred.
- Classify as "successful" if:
  * The receipt indicates a completed transfer or deposit (e.g. contains "Transferred to", "Transferred", "Transfer Successful", "Transaction Successful", "Fund Transfer", "Cash Deposit", "Deposit", "Debit Voucher", "Paid", "Success").
  * Or the receipt shows transaction confirmation details (such as Reference No, Date/Time, Amount, To Account) with NO indicator of failure or error. If there is no sign of failure, it MUST be classified as "successful".
- Classify as "failed" ONLY if the image explicitly displays an error or failure message (e.g. "Failed", "Transaction Failed", "Declined", "Cancelled", "Rejected", "Error", "Insufficient Balance").
- Classify as "pending" ONLY if explicitly marked as pending, scheduled, or processing.
- Do NOT classify as "unknown" if the image is a valid transaction receipt, memo, or deposit slip.

For transaction_time, use the date and time printed on the receipt (Bangladesh local time, Asia/Dhaka), normalized to "YYYY-MM-DD HH:mm:ss". All example receipts include a time. Do not use the phone status-bar time, file metadata, upload time, or current server time.
For amount, use the transfer/deposit amount. Put any service charge and VAT in fee, and the full debit/grand total in total_amount when shown.

Return a STRICT JSON object with these fields:
- "provider": string ("city_bank", "dbbl", "brac_bank", "ebl", "bkash", "nagad", "rocket", "upay", or "other")
- "trx_id": string or null (Primary Transaction ID, e.g. "LID02226212657", "9K83XJ8KL2", "00906359"; for City Bank "Reference No" and BRAC Bank "Reference Number", copy the same value here)
- "reference": string or null (Reference Number, e.g. "100019139250", "550100626")
- "amount": decimal number (The transfer or deposit amount in BDT, e.g. 6400.00, 8320.00, 44100.00, 8000.00)
- "fee": decimal number (Service charge / fee, otherwise 0.0)
- "total_amount": decimal number (Total including fee)
- "balance": decimal number or null (Account balance shown on the receipt, otherwise null)
- "txn_type": string or null (Transaction type, transfer channel, or deposit type as printed)
- "beneficiary_bank": string or null (Beneficiary bank name if shown)
- "sender_account": string or null (From/Sender account identifier exactly as displayed)
- "sender_number": string or null (Compatibility alias for sender_account; return the same value)
- "sender_name": string or null (Name explicitly shown for the From/Sender account)
- "receiver_account": string or null (To/Transferred to/Beneficiary account identifier exactly as displayed)
- "receiver_number": string or null (Compatibility alias for receiver_account; return the same value)
- "receiver_name": string or null (Beneficiary name, e.g. "CYBER 32", "SHAHIN VAI DOLLAR")
- "transaction_time": string or null ("YYYY-MM-DD HH:mm:ss" or null)
- "status_in_screenshot": string ("successful", "completed", "pending", "failed", "unknown")
- "is_suspicious_or_edited": boolean (true if Photoshop artifacts, altered fonts, or fake receipt detected; false otherwise)
- "confidence_score": float between 0.0 and 1.0 (0.0 if image is blank/black/unreadable)
- "notes": string summary of extracted data
PROMPT;

        try {
            $endpoint = "{$baseUrl}/models/{$model}:generateContent";

            $response = Http::withHeader('x-goog-api-key', $apiKey)
                ->connectTimeout(15)
                ->timeout(45)
                ->retry(3, 1000, fn () => true, false)
                ->post($endpoint, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $imageDetails['mime_type'],
                                        'data' => base64_encode($imageDetails['bytes']),
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'response_mime_type' => 'application/json',
                        'temperature' => 0.1,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini API call failed', [
                    'status' => $response->status(),
                ]);

                return $this->fallbackResponse('Gemini API returned HTTP '.$response->status().'.');
            }

            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $decoded = json_decode($rawText, true);

            if (! is_array($decoded)) {
                Log::error('Failed to decode Gemini response JSON', ['raw_text' => $rawText]);

                return $this->fallbackResponse('Invalid JSON returned by Gemini');
            }

            $provider = $decoded['provider'] ?? null;
            $beneficiaryBank = $decoded['beneficiary_bank'] ?? null;
            $txnType = $decoded['txn_type'] ?? $decoded['transaction_type'] ?? null;

            // Normalize MFS to Bank transfers to the receiving bank provider
            if (! empty($beneficiaryBank) || ($txnType !== null && preg_match('/(?:to\s*bank|টু\s*ব্যাংক)/ui', (string) $txnType))) {
                $bankTarget = mb_strtolower((string) ($beneficiaryBank ?: $txnType));
                if (str_contains($bankTarget, 'city') || str_contains($bankTarget, 'সিটি')) {
                    $provider = 'city_bank';
                } elseif (str_contains($bankTarget, 'brac') || str_contains($bankTarget, 'ব্র্যাক')) {
                    $provider = 'brac_bank';
                } elseif (str_contains($bankTarget, 'dbbl') || str_contains($bankTarget, 'dutch') || str_contains($bankTarget, 'ডাচ') || str_contains($bankTarget, 'nexus')) {
                    $provider = 'dbbl';
                } elseif (str_contains($bankTarget, 'ebl') || str_contains($bankTarget, 'eastern') || str_contains($bankTarget, 'ইস্টার্ন')) {
                    $provider = 'ebl';
                }
            }

            $reference = $decoded['reference'] ?? $decoded['ref'] ?? null;
            $rawTrxId = $decoded['trx_id'] ?? $decoded['transaction_id'] ?? null;

            if ($rawTrxId === null && $reference !== null && in_array($provider, ['city_bank', 'brac_bank'], true)) {
                $rawTrxId = $reference;
            }

            $trxId = $rawTrxId !== null ? strtoupper(trim((string) $rawTrxId)) : null;
            if ($trxId !== null) {
                $trxId = preg_replace('/[^A-Z0-9]/', '', $trxId);
            }

            $senderAccount = $this->normalizeAccount($decoded['sender_account'] ?? $decoded['from_account'] ?? $decoded['sender_number'] ?? null);
            $receiverAccount = $this->normalizeAccount($decoded['receiver_account'] ?? $decoded['to_account'] ?? $decoded['receiver_number'] ?? null);

            return [
                'provider' => $provider,
                'trx_id' => $trxId,
                'amount' => isset($decoded['amount']) ? (float) $decoded['amount'] : null,
                'fee' => isset($decoded['fee']) ? (float) $decoded['fee'] : 0.0,
                'total_amount' => isset($decoded['total_amount']) ? (float) $decoded['total_amount'] : null,
                'balance' => isset($decoded['balance']) ? (float) $decoded['balance'] : null,
                'txn_type' => $decoded['txn_type'] ?? $decoded['transaction_type'] ?? null,
                'beneficiary_bank' => $decoded['beneficiary_bank'] ?? null,
                'sender_account' => $senderAccount,
                'sender_number' => $senderAccount,
                'sender_name' => $decoded['sender_name'] ?? $decoded['from_name'] ?? null,
                'receiver_account' => $receiverAccount,
                'receiver_number' => $receiverAccount,
                'receiver_name' => $decoded['receiver_name'] ?? $decoded['to_name'] ?? $decoded['beneficiary_name'] ?? null,
                'reference' => $reference,
                'transaction_time' => $decoded['transaction_time'] ?? null,
                'status_in_screenshot' => $decoded['status_in_screenshot'] ?? 'unknown',
                'is_suspicious_or_edited' => (bool) ($decoded['is_suspicious_or_edited'] ?? false),
                'confidence_score' => (float) ($decoded['confidence_score'] ?? 0.0),
                'notes' => $decoded['notes'] ?? null,
                'raw_response' => $decoded,
            ];
        } catch (Exception $e) {
            Log::error('GeminiVisionService could not contact the Gemini API.', [
                'exception' => $e::class,
            ]);

            return $this->fallbackResponse('Gemini API request failed before a response was received.');
        }
    }

    /**
     * @return array{mime_type: string, bytes: string}|null
     */
    private function resolveImageData(string $path): ?array
    {
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            $bytes = $disk->get($path);
            if (! is_string($bytes) || $bytes === '') {
                return null;
            }
            $mime = $disk->mimeType($path);
            $mimeType = is_string($mime) && $mime !== '' ? $mime : 'image/jpeg';

            return ['mime_type' => $mimeType, 'bytes' => $bytes];
        }

        if (file_exists($path)) {
            $bytes = file_get_contents($path);
            if (! is_string($bytes) || $bytes === '') {
                return null;
            }
            $mime = mime_content_type($path);
            $mimeType = is_string($mime) && $mime !== '' ? $mime : 'image/jpeg';

            return ['mime_type' => $mimeType, 'bytes' => $bytes];
        }

        return null;
    }

    private function normalizeAccount(mixed $account): ?string
    {
        if (! is_string($account) && ! is_numeric($account)) {
            return null;
        }

        $normalizedAccount = trim((string) preg_replace('/^(?:account|a\/c|acc|ac)\s*[:#-]?\s*/i', '', trim((string) $account)));

        return $normalizedAccount !== '' ? $normalizedAccount : null;
    }

    /**
     * @return array{
     *     provider: null,
     *     trx_id: null,
     *     amount: null,
     *     fee: null,
     *     total_amount: null,
     *     balance: null,
     *     txn_type: null,
     *     beneficiary_bank: null,
     *     sender_account: null,
     *     sender_number: null,
     *     sender_name: null,
     *     receiver_account: null,
     *     receiver_number: null,
     *     reference: null,
     *     transaction_time: null,
     *     status_in_screenshot: string,
     *     is_suspicious_or_edited: bool,
     *     confidence_score: float,
     *     notes: string
     * }
     */
    private function fallbackResponse(string $reason): array
    {
        return [
            'provider' => null,
            'trx_id' => null,
            'amount' => null,
            'fee' => null,
            'total_amount' => null,
            'balance' => null,
            'txn_type' => null,
            'beneficiary_bank' => null,
            'sender_account' => null,
            'sender_number' => null,
            'sender_name' => null,
            'receiver_account' => null,
            'receiver_number' => null,
            'receiver_name' => null,
            'reference' => null,
            'transaction_time' => null,
            'status_in_screenshot' => 'error',
            'is_suspicious_or_edited' => false,
            'confidence_score' => 0.0,
            'notes' => $reason,
        ];
    }
}
