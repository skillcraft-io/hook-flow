<?php

namespace Skillcraft\HookFlow\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Support\HookExecutor;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use ReflectionClass;

class HookExecutorTest extends TestCase
{
    private HookExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executor = new HookExecutor();
    }

    #[Test]
    public function it_can_execute_action_hook(): void
    {
        $executed = false;
        $hook = new class('test-hook') extends TestHook {
            public function execute(array $args): void
            {
                $GLOBALS['executed'] = true;
            }

            public function getParameters(): array
            {
                return ['param1' => 'string', 'param2' => 'int'];
            }
        };

        $this->executor->execute($hook, ['param1' => 'value1', 'param2' => 42]);
        
        $this->assertTrue($GLOBALS['executed']);
        unset($GLOBALS['executed']);
    }

    #[Test]
    public function it_can_execute_filter_hook(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function isFilter(): bool
            {
                return true;
            }

            public function apply($value, array $args): mixed
            {
                return $args['param1'] . '_filtered';
            }

            public function getParameters(): array
            {
                return ['param1' => 'string', 'param2' => 'int'];
            }
        };

        $result = $this->executor->execute($hook, ['param1' => 'value1', 'param2' => 42]);
        
        $this->assertSame('value1_filtered', $result);
    }

    #[Test]
    public function it_validates_argument_types(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function getParameters(): array
            {
                return ['param1' => 'string', 'param2' => 'int'];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->executor->execute($hook, ['param1' => 123, 'param2' => 'not-an-int']);
    }

    #[Test]
    public function it_validates_required_arguments(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function getParameters(): array
            {
                return ['required' => 'string'];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->executor->execute($hook, ['wrong' => 'value']);
    }

    private function invokeValidateArgumentType(string $expectedType, mixed $value): bool
    {
        $reflection = new ReflectionClass($this->executor);
        $method = $reflection->getMethod('validateArgumentType');
        $method->setAccessible(true);
        return $method->invoke($this->executor, $value, $expectedType);
    }

    #[Test]
    public function it_validates_argument_type_string(): void
    {
        $this->assertTrue($this->invokeValidateArgumentType('string', 'test'));
        $this->assertFalse($this->invokeValidateArgumentType('string', 123));
    }

    #[Test]
    public function it_validates_argument_type_int(): void
    {
        $this->assertTrue($this->invokeValidateArgumentType('int', 123));
        $this->assertFalse($this->invokeValidateArgumentType('int', 'test'));
    }

    #[Test]
    public function it_validates_argument_type_float(): void
    {
        $this->assertTrue($this->invokeValidateArgumentType('float', 123.45));
        $this->assertFalse($this->invokeValidateArgumentType('float', 'test'));
    }

    #[Test]
    public function it_validates_argument_type_bool(): void
    {
        $this->assertTrue($this->invokeValidateArgumentType('bool', true));
        $this->assertFalse($this->invokeValidateArgumentType('bool', 'test'));
    }

    #[Test]
    public function it_validates_argument_type_array(): void
    {
        $this->assertTrue($this->invokeValidateArgumentType('array', ['test']));
        $this->assertFalse($this->invokeValidateArgumentType('array', 'test'));
    }

    #[Test]
    public function it_validates_argument_type_object(): void
    {
        $this->assertTrue($this->invokeValidateArgumentType('object', new \stdClass()));
        $this->assertFalse($this->invokeValidateArgumentType('object', 'test'));
    }
}
