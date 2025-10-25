<?php

namespace AnyTech\Jinah\Exceptions;

class ApiException extends JinahException
{
    public static function connectionFailed(string $url, ?string $reason = null): self
    {
        $message = "Failed to connect to API: {$url}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        return new self($message, 500);
    }

    public static function authenticationFailed(): self
    {
        return new self("Authentication failed. Please check your API credentials.", 401);
    }

    public static function rateLimitExceeded(): self
    {
        return new self("API rate limit exceeded. Please try again later.", 429);
    }

    public static function invalidResponse(string $response): self
    {
        return new self("Invalid API response received", 502, null, ['response' => $response]);
    }

    public static function serverError(int $statusCode, string $message): self
    {
        return new self("API server error ({$statusCode}): {$message}", $statusCode);
    }

    public static function timeout(): self
    {
        return new self("API request timed out", 408);
    }
}