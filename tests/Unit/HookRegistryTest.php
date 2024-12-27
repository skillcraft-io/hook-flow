<?php

namespace Skillcraft\HookFlow\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Support\HookRegistry;
use Skillcraft\HookFlow\Support\HookExecutor;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;

class HookRegistryTest extends TestCase
{
    private HookRegistry $registry;
    private HookExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->executor = new HookExecutor();
        $this->registry = new HookRegistry($this->executor);
    }

    #[Test]
    public function it_can_register_hook(): void
    {
        $hook = new TestHook();
        $this->registry->register($hook);

        $this->assertSame($hook, $this->registry->get('test-hook'));
    }

    #[Test]
    public function it_can_register_many_hooks(): void
    {
        $hooks = [
            new class('hook1') extends TestHook {},
            new class('hook2') extends TestHook {}
        ];

        $this->registry->registerMany($hooks);

        $this->assertSame($hooks[0], $this->registry->get('hook1'));
        $this->assertSame($hooks[1], $this->registry->get('hook2'));
    }

    #[Test]
    public function it_can_get_all_hooks(): void
    {
        $hooks = [
            new class('hook1') extends TestHook {},
            new class('hook2') extends TestHook {}
        ];

        $this->registry->registerMany($hooks);

        $allHooks = $this->registry->all();
        $this->assertCount(2, $allHooks);
        $this->assertTrue($allHooks->contains($hooks[0]));
        $this->assertTrue($allHooks->contains($hooks[1]));
    }

    #[Test]
    public function it_can_execute_action_hook(): void
    {
        $executed = false;
        $hook = new class('action-hook') extends TestHook {
            public function execute(array $args): void
            {
                $GLOBALS['executed'] = true;
            }
        };

        $this->registry->register($hook);
        $this->registry->execute('action-hook', ['param1' => 'value1', 'param2' => 42]);
        
        $this->assertTrue($GLOBALS['executed']);
        unset($GLOBALS['executed']);
    }

    #[Test]
    public function it_can_execute_filter_hook(): void
    {
        $hook = new class('filter-hook') extends TestHook {
            public function isFilter(): bool
            {
                return true;
            }

            public function apply($value, array $args): mixed
            {
                return $value . '_filtered';
            }
        };

        $this->registry->register($hook);
        $result = $this->registry->execute('filter-hook', ['value' => 'value1']);
        
        $this->assertSame('value1_filtered', $result);
    }

    #[Test]
    public function it_can_check_if_hook_exists(): void
    {
        $hook = new TestHook();
        $this->registry->register($hook);

        $this->assertTrue($this->registry->has('test-hook'));
        $this->assertFalse($this->registry->has('non-existent'));
    }

    #[Test]
    public function it_can_get_all_hooks_for_plugin(): void
    {
        $hooks = [
            new class('hook1') extends TestHook {
                public function getPlugin(): string { return 'plugin1'; }
            },
            new class('hook2') extends TestHook {
                public function getPlugin(): string { return 'plugin1'; }
            },
            new class('hook3') extends TestHook {
                public function getPlugin(): string { return 'plugin2'; }
            }
        ];

        $this->registry->registerMany($hooks);

        $pluginHooks = $this->registry->forPlugin('plugin1');
        $this->assertCount(2, $pluginHooks);
        $this->assertTrue($pluginHooks->contains($hooks[0]));
        $this->assertTrue($pluginHooks->contains($hooks[1]));
    }

    #[Test]
    public function it_can_get_filter_hooks(): void
    {
        $hooks = [
            new class('filter') extends TestHook {
                public function isFilter(): bool { return true; }
            },
            new class('action') extends TestHook {
                public function isFilter(): bool { return false; }
            }
        ];

        $this->registry->registerMany($hooks);

        $filters = $this->registry->filters();
        $this->assertCount(1, $filters);
        $this->assertTrue($filters->contains($hooks[0]));
    }

    #[Test]
    public function it_can_get_action_hooks(): void
    {
        $hooks = [
            new class('filter') extends TestHook {
                public function isFilter(): bool { return true; }
            },
            new class('action') extends TestHook {
                public function isFilter(): bool { return false; }
            }
        ];

        $this->registry->registerMany($hooks);

        $actions = $this->registry->actions();
        $this->assertCount(1, $actions);
        $this->assertTrue($actions->contains($hooks[1]));
    }

    #[Test]
    public function it_can_remove_hook(): void
    {
        $hook = new TestHook();
        $this->registry->register($hook);
        $this->assertTrue($this->registry->has('test-hook'));

        $this->registry->remove('test-hook');
        $this->assertFalse($this->registry->has('test-hook'));
    }

    #[Test]
    public function it_can_clear_all_hooks(): void
    {
        $hooks = [
            new class('hook1') extends TestHook {},
            new class('hook2') extends TestHook {}
        ];

        $this->registry->registerMany($hooks);
        $this->assertCount(2, $this->registry->all());

        $this->registry->clear();
        $this->assertCount(0, $this->registry->all());
    }
}
