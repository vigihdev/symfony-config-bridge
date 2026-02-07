<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Exception;

final class ConfigBridgeException extends AbstractConfigBridgeException
{

    public static function handleFromThrowable(\Throwable $e): static
    {
        return new static($e->getMessage(), $e->getCode(), $e);
    }

    public static function notReadable(string $filepath): static
    {
        return new static(sprintf("%s is not readable", $filepath));
    }

    public static function notWritable(string $filepath): static
    {
        return new static(sprintf("%s is not writable", $filepath));
    }

    public static function directoryNotFound(string $directory): static
    {
        return new static(sprintf("Directory not found: %s", $directory));
    }

    public static function classNotFound(string $className): static
    {
        return new static(sprintf("Class not found: %s", $className));
    }

    public static function fileNotFound(string $filepath): static
    {
        return new static(sprintf("File not found: %s", $filepath));
    }
}
