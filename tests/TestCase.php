<?php

declare(strict_types=1);

namespace Vigihdev\Symfony\ConfigBridge\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{

    /**
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
