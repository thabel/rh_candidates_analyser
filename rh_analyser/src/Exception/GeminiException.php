<?php

namespace App\Exception;

/**
 * Exception personnalisée pour les erreurs de l'API Gemini
 */
class GeminiException extends \Exception
{
    private int $statusCode;

    public function __construct(
        string $message = 'Erreur API Gemini',
        int $statusCode = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
