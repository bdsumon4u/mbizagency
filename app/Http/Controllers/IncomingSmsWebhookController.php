<?php

namespace App\Http\Controllers;

use App\Enums\IncomingSmsStatus;
use App\Jobs\ProcessPendingPaymentVerificationsJob;
use App\Services\IncomingSmsWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomingSmsWebhookController extends Controller
{
    public function __construct(
        private IncomingSmsWebhookService $webhookService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->webhookService->isValidSecret($request)) {
            return response()->json([
                'error' => 'Unauthorized. Invalid or missing webhook secret key.',
            ], 401);
        }

        $incomingSms = $this->webhookService->handleRequest($request);

        if (! $incomingSms) {
            return response()->json([
                'error' => 'Invalid payload. Message text is required. Ensure your JSON body format has valid quotes: {"from": "{sender}", "text": "{msg}"}',
            ], 422);
        }

        // Trigger matching & auto-verification pipeline across all pending submissions
        ProcessPendingPaymentVerificationsJob::dispatch();

        return response()->json([
            'ok' => true,
            'sms_id' => $incomingSms->id,
            'source' => $incomingSms->source,
            'provider' => $incomingSms->provider,
            'trx_id' => $incomingSms->trx_id,
            'amount' => $incomingSms->amount !== null ? (float) $incomingSms->amount : null,
            'status' => $incomingSms->status instanceof IncomingSmsStatus
                ? $incomingSms->status->value
                : (string) ($incomingSms->status ?? 'unmatched'),
        ]);
    }
}
