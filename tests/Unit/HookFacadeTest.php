<?php

namespace Skillcraft\HookFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Facades\Hook;
use Skillcraft\HookFlow\Support\HookRegistry;
use Skillcraft\HookFlow\Support\HookExecutor;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use Skillcraft\HookFlow\Tests\Doubles\InvalidTestHook;
use Illuminate\Support\Facades\Facade;

class HookFacadeTest extends TestCase
{
    private HookRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new HookRegistry(new HookExecutor());
        Hook::swap($this->registry);
    }

    #[Test]
    public function it_can_use_facade_to_register_hooks(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->assertNotNull(Hook::get($hook->getIdentifier()));
    }

    #[Test]
    public function it_can_use_facade_to_get_all_hooks(): void
    {
        $hooks = [
            new TestHook('test1'),
            new TestHook('test2'),
        ];

        Hook::registerMany($hooks);

        $this->assertCount(2, Hook::all());
    }

    #[Test]
    public function it_can_use_facade_to_get_hooks_by_plugin(): void
    {
        $hook1 = new TestHook('test1', null, 'plugin1');
        $hook2 = new TestHook('test2', null, 'plugin1');
        $hook3 = new TestHook('test3', null, 'plugin2');

        Hook::registerMany([$hook1, $hook2, $hook3]);

        $plugin1Hooks = Hook::forPlugin('plugin1');
        $plugin2Hooks = Hook::forPlugin('plugin2');

        $this->assertCount(2, $plugin1Hooks);
        $this->assertCount(1, $plugin2Hooks);
        $this->assertTrue($plugin1Hooks->contains(fn ($hook) => $hook->getIdentifier() === 'test1'));
        $this->assertTrue($plugin1Hooks->contains(fn ($hook) => $hook->getIdentifier() === 'test2'));
        $this->assertTrue($plugin2Hooks->contains(fn ($hook) => $hook->getIdentifier() === 'test3'));
    }

    #[Test]
    public function it_can_use_facade_to_remove_hooks(): void
    {
        $hook = new TestHook();
        Hook::register($hook);
        Hook::remove($hook->getIdentifier());

        $this->assertNull(Hook::get($hook->getIdentifier()));
    }

    #[Test]
    public function it_can_use_facade_to_clear_all_hooks(): void
    {
        $hooks = [
            new TestHook('test1'),
            new TestHook('test2'),
        ];

        Hook::registerMany($hooks);
        Hook::clear();

        $this->assertTrue(Hook::all()->isEmpty());
    }

    #[Test]
    public function it_validates_hooks_through_facade(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Hook::register(new InvalidTestHook());
    }

    #[Test]
    public function it_returns_null_for_non_existent_hook_through_facade(): void
    {
        $this->assertNull(Hook::get('non-existent'));
    }

    #[Test]
    public function it_executes_hooks_through_facade(): void
    {
        $executed = [];
        $hook = new class('test', 'plugin') extends TestHook {
            public function execute(array $args): void
            {
                $args['executed'][] = 'hook1';
            }
        };

        Hook::register($hook);
        Hook::execute('test', [
            'param1' => 'value1',
            'param2' => 42,
            'executed' => &$executed
        ]);

        $this->assertEquals(['hook1'], $executed);
    }

    #[Test]
    public function it_returns_filtered_value_from_filter_hooks_through_facade(): void
    {
        $hook = new class('test', 'plugin') extends TestHook {
            public function isFilter(): bool
            {
                return true;
            }

            public function apply($value, array $args): mixed
            {
                return $args['value'] . '_filtered';
            }
        };

        Hook::register($hook);
        $result = Hook::execute('test', [
            'param1' => 'value1',
            'param2' => 42,
            'value' => 'test'
        ]);

        $this->assertEquals('test_filtered', $result);
    }
}
