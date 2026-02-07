<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\DependencyInjection;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Inject
{
    public function __construct(
        public string $serviceName
    ) {}
}
