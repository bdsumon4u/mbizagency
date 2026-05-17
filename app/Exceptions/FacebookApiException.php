<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

final class FacebookApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly Response $response,
        public readonly int $fbErrorCode = 0,
        public readonly int $fbErrorSubcode = 0
    ) {
        parent::__construct($message);
    }

    public function isTokenExpired(): bool
    {
        return $this->fbErrorCode === 190;
    }
}
