<?php

namespace Skillcraft\HookFlow\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Skillcraft\HookFlow\HookFlowServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            HookFlowServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Hook' => 'Skillcraft\HookFlow\Facades\Hook',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Remove error handlers that interfere with testing
        restore_error_handler();
        restore_exception_handler();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
