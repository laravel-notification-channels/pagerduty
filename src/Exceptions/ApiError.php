<?php

namespace NotificationChannels\PagerDuty\Exceptions;

use Exception;

class ApiError extends Exception
{
    public static function serviceBadRequest(string $response): static
    {
        $response = json_decode($response, true);

        $message = $response['message'] ?? '';
        $errors = isset($response['errors']) ? implode(',', $response['errors']) : '';

        return new static("PagerDuty returned 400 Bad Request: $message - $errors");
    }

    public static function rateLimit(): static
    {
        // https://v2.developer.pagerduty.com/docs/errors
        return new static('PagerDuty returned 429 Too Many Requests');
    }

    public static function unknownError(int $code): static
    {
        return new static("PagerDuty responded with an unexpected HTTP Status: $code");
    }
}
