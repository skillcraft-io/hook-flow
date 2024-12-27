<?php

namespace Skillcraft\HookFlow\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Tests\TestCase;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use Skillcraft\HookFlow\Tests\Doubles\InvalidTestHook;

class HookDefinitionTest extends TestCase
{
    #[Test]
    public function it_validates_hook_definition(): void
    {
        $hook = new TestHook();
        $issues = $hook->validate();
        $this->assertTrue($issues->isEmpty());
    }

    #[Test]
    public function it_detects_empty_identifier(): void
    {
        $hook = new InvalidTestHook();
        $issues = $hook->validate();

        $this->assertTrue($issues->contains('Hook identifier cannot be empty'));
    }

    #[Test]
    public function it_detects_invalid_identifier(): void
    {
        $hook = new InvalidTestHook('INVALID@ID');
        $issues = $hook->validate();

        $this->assertTrue($issues->contains('Hook identifier should only contain letters, numbers, underscores, backslashes, and hyphens'));
    }

    #[Test]
    public function it_detects_empty_description(): void
    {
        $hook = new InvalidTestHook('valid-id');
        $issues = $hook->validate();

        $this->assertTrue($issues->contains('Hook description cannot be empty'));
    }

    #[Test]
    public function it_detects_empty_plugin(): void
    {
        $hook = new InvalidTestHook('valid-id', 'Description');
        $issues = $hook->validate();

        $this->assertTrue($issues->contains('Hook plugin cannot be empty'));
    }

    #[Test]
    public function it_detects_invalid_parameter_name(): void
    {
        $hook = new class('valid-id', 'Description', 'plugin') extends InvalidTestHook {
            public function getParameters(): array
            {
                return ['invalid@param' => 'string'];
            }
        };

        $issues = $hook->validate();

        $this->assertTrue($issues->contains("Invalid parameter name 'invalid@param'"));
    }

    #[Test]
    public function it_detects_empty_parameter_type(): void
    {
        $hook = new class('valid-id', 'Description', 'plugin') extends InvalidTestHook {
            public function getParameters(): array
            {
                return ['param' => ''];
            }
        };

        $issues = $hook->validate();

        $this->assertTrue($issues->contains("Parameter 'param' has no type specified"));
    }

    #[Test]
    public function it_validates_valid_hook(): void
    {
        $hook = new TestHook();
        $issues = $hook->validate();

        $this->assertTrue($issues->isEmpty());
    }
}
