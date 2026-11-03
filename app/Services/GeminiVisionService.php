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
     *     sender_number: ?string,
     *     receiver_number: ?string,
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
        $model = config('payment_verification.gemini.model', 'gemini-2.5-flash');
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

Inspect the image thoroughly and extract all transaction details.

Return a STRICT JSON object with these fields:
- "provider": string ("city_bank", "dbbl", "brac_bank", "ebl", "bkash", "nagad", "rocket", "upay", or "other")
- "trx_id": string or null (Primary Transaction ID, e.g. "LID02226212657", "9K83XJ8KL2", "00906359")
- "reference": string or null (Reference Number, e.g. "100019139250", "550100626")
- "amount": decimal number (The transfer or deposit amount in BDT, e.g. 6400.00, 8320.00, 44100.00, 8000.00)
- "fee": decimal number (Service charge / fee, otherwise 0.0)
- "total_amount": decimal number (Total including fee)
- "sender_number": string or null (Sender account number, phone, card number, or NexusPay ID)
- "receiver_number": string or null (Beneficiary / To Account / Recipient account number, e.g. "1503915274001", "1641100042219", "2053944080001")
- "receiver_name": string or null (Beneficiary name, e.g. "CYBER 32", "SHAHIN VAI DOLLAR")
- "transaction_time": string or null ("YYYY-MM-DD HH:mm:ss" or null)
- "status_in_screenshot": string ("successful", "completed", "pending", "failed", "unknown")
- "is_suspicious_or_edited": boolean (true if Photoshop artifacts, altered fonts, or fake receipt detected; false otherwise)
- "confidence_score": float between 0.0 and 1.0 (0.0 if image is blank/black/unreadable)
- "notes": string summary of extracted data
PROMPT;

        try {
            $endpoint = "{$baseUrl}/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(30)->post($endpoint, [
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
                    'body' => $response->body(),
                ]);

                return $this->fallbackResponse('Gemini API error: '.$response->status());
            }

            $responseData = $response->json();
            $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $decoded = json_decode($rawText, true);

            if (! is_array($decoded)) {
                Log::error('Failed to decode Gemini response JSON', ['raw_text' => $rawText]);

                return $this->fallbackResponse('Invalid JSON returned by Gemini');
            }

            $trxId = isset($decoded['trx_id']) ? strtoupper(trim((string) $decoded['trx_id'])) : null;
            if ($trxId !== null) {
                $trxId = preg_replace('/[^A-Z0-9]/', '', $trxId);
            }

            return [
                'provider' => $decoded['provider'] ?? null,
                'trx_id' => $trxId,
                'amount' => isset($decoded['amount']) ? (float) $decoded['amount'] : null,
                'fee' => isset($decoded['fee']) ? (float) $decoded['fee'] : 0.0,
                'total_amount' => isset($decoded['total_amount']) ? (float) $decoded['total_amount'] : null,
                'sender_number' => $decoded['sender_number'] ?? null,
                'receiver_number' => $decoded['receiver_number'] ?? null,
                'reference' => $decoded['reference'] ?? null,
                'transaction_time' => $decoded['transaction_time'] ?? null,
                'status_in_screenshot' => $decoded['status_in_screenshot'] ?? 'unknown',
                'is_suspicious_or_edited' => (bool) ($decoded['is_suspicious_or_edited'] ?? false),
                'confidence_score' => (float) ($decoded['confidence_score'] ?? 0.0),
                'notes' => $decoded['notes'] ?? null,
                'raw_response' => $decoded,
            ];
        } catch (Exception $e) {
            Log::error('Exception in GeminiVisionService: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->fallbackResponse('Exception: '.$e->getMessage());
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

    /**
     * @return array{
     *     provider: null,
     *     trx_id: null,
     *     amount: null,
     *     fee: null,
     *     total_amount: null,
     *     sender_number: null,
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
            'sender_number' => null,
            'receiver_number' => null,
            'reference' => null,
            'transaction_time' => null,
            'status_in_screenshot' => 'unknown',
            'is_suspicious_or_edited' => false,
            'confidence_score' => 0.0,
            'notes' => $reason,
        ];
    }
}
