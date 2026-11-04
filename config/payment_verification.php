<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cooling-Off Verification Window (Minutes)
    |--------------------------------------------------------------------------
    |
    | The safety window time to wait before executing auto-approval. This allows
    | duplicate submissions or conflicting claims for the same SMS or TrxID
    | to be detected and held for manual review. Default is 5 minutes.
    |
    */
    'window_time_minutes' => (int) env('PAYMENT_VERIFICATION_WINDOW_MINUTES', 5),

    'sms_match_window_minutes' => (int) env('PAYMENT_VERIFICATION_SMS_MATCH_WINDOW_MINUTES', 5),

    /*
    |--------------------------------------------------------------------------
    | Maximum Wait Time For Incoming SMS (Minutes)
    |--------------------------------------------------------------------------
    |
    | How long to keep a pending submission waiting for an incoming Telegram SMS
    | before moving it to manual review status.
    |
    */
    'max_wait_time_minutes' => (int) env('PAYMENT_VERIFICATION_MAX_WAIT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Auto-Approve Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, transactions meeting all verification criteria without any
    | conflicts or suspicion will be approved automatically.
    |
    */
    'auto_approve' => (bool) env('PAYMENT_VERIFICATION_AUTO_APPROVE', true),

    /*
    |--------------------------------------------------------------------------
    | AI Confidence Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum confidence score (0.0 to 1.0) returned by the AI model to allow
    | auto-approval.
    |
    */
    'confidence_threshold' => (float) env('PAYMENT_VERIFICATION_CONFIDENCE_THRESHOLD', 0.85),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Vision Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and model settings for screenshot multimodal analysis.
    | The Flash-Lite model provides low-cost multimodal OCR and extraction.
    |
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Direct HTTP REST Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Secret key / API token for apps forwarding SMS directly via HTTP POST.
    |
    */
    'webhook' => [
        'secret_key' => env('SMS_WEBHOOK_SECRET', env('SMS_WEBHOOK_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Webhook & Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Config for receiving forwarded SMS from Telegram channels or groups.
    |
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        // Comma-separated list of allowed chat / channel IDs (optional for extra security)
        'allowed_chat_ids' => array_filter(array_map('trim', explode(',', env('TELEGRAM_ALLOWED_CHAT_IDS', '')))),
    ],
];
