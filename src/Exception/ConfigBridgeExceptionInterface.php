<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Exception;

interface ConfigBridgeExceptionInterface extends \Throwable
{
    public function getContext(): array;

    public function toArray(): array;

    public function getFormattedMessage(): string;
}
