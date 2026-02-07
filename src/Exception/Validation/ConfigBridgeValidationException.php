<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Exception\Validation;

use Vigihdev\Symfony\ConfigBridge\Exception\ConfigBridgeException;

final class ConfigBridgeValidationException
{

    public function __construct(
        private readonly string $filepathOrDirectory,
    ) {}

    public static function validate(string $filepathOrDirectory): self
    {
        return new self($filepathOrDirectory);
    }

    public function mustBeFile(): self
    {
        $filepath = $this->filepathOrDirectory;
        if (!is_file($filepath)) {
            throw ConfigBridgeException::fileNotFound($filepath);
        }

        return $this;
    }

    public function mustBeDirectory(): self
    {
        $directory = $this->filepathOrDirectory;
        if (!is_dir($directory)) {
            throw ConfigBridgeException::directoryNotFound($directory);
        }

        return $this;
    }

    public function mustBeReadable(): self
    {
        $filepath = $this->filepathOrDirectory;
        if (!is_readable($filepath)) {
            throw ConfigBridgeException::notReadable($filepath);
        }

        return $this;
    }
}
