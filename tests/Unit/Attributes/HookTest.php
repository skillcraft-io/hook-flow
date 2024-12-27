<?php

namespace Skillcraft\HookFlow\Tests\Unit\Attributes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Attributes\Hook;

class HookTest extends TestCase
{
    #[Test]
    public function it_can_create_hook_attribute(): void
    {
        $hook = new Hook(
            identifier: 'test-hook',
            description: 'Test Description',
            parameters: ['param1' => 'string'],
            triggerPoint: 'TestController@test:before'
        );

        $this->assertSame('test-hook', $hook->identifier);
        $this->assertSame('Test Description', $hook->description);
        $this->assertSame(['param1' => 'string'], $hook->parameters);
        $this->assertSame('TestController@test:before', $hook->triggerPoint);
    }

    #[Test]
    public function it_can_create_hook_with_minimal_params(): void
    {
        $hook = new Hook('test-hook');

        $this->assertSame('test-hook', $hook->identifier);
        $this->assertNull($hook->description);
        $this->assertNull($hook->parameters);
        $this->assertNull($hook->triggerPoint);
    }
}
