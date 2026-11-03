<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPendingPaymentVerificationsJob;
use App\Services\TelegramWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramSmsWebhookController extends Controller
{
    public function __construct(
        private TelegramWebhookService $telegramService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->telegramService->isValidSecret($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $incomingSms = $this->telegramService->handlePayload($request->all());

        if ($incomingSms) {
            // Trigger verification check across pending submissions
            ProcessPendingPaymentVerificationsJob::dispatch();
        }

        return response()->json([
            'ok' => true,
            'ingested' => $incomingSms !== null,
            'sms_id' => $incomingSms?->id,
            'trx_id' => $incomingSms?->trx_id,
        ]);
    }
}
