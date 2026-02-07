<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Exception;

use Exception;

abstract class AbstractConfigBridgeException extends Exception implements ConfigBridgeExceptionInterface
{

    /**
     * Additional context data
     */
    protected array $context = [];

    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = []
    ) {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();

        if (!empty($this->context)) {
            $contextStr = json_encode($this->context, JSON_UNESCAPED_SLASHES);
            $message .= " (context: {$contextStr})";
        }
        return $message;
    }

    /**
     * Convert to array for logging/API responses
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'exception' => static::class,
        ];
    }
}
