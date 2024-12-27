<?php

namespace Skillcraft\HookFlow\Tests\Unit\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Tests\TestCase;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use Skillcraft\HookFlow\Facades\Hook;

class ListHooksCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Hook::clear();
    }

    #[Test]
    public function it_can_list_hooks_in_table_format(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->artisan('hook:flows:list')
            ->expectsTable(
                ['Identifier', 'Description', 'Plugin', 'Parameters', 'Trigger Point'],
                [[$hook->getIdentifier(), $hook->getDescription(), $hook->getPlugin(), 'param1: string, param2: int', $hook->getTriggerPoint() ?: '<none>']]
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_list_hooks_in_json_format(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->artisan('hook:flows:list', ['--json' => true])
            ->expectsOutput(json_encode([
                [
                    'identifier' => $hook->getIdentifier(),
                    'description' => $hook->getDescription(),
                    'plugin' => $hook->getPlugin(),
                    'parameters' => $hook->getParameters(),
                    'trigger_point' => $hook->getTriggerPoint()
                ]
            ], JSON_PRETTY_PRINT))
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_filter_by_plugin(): void
    {
        $hook1 = new TestHook('test1', null, 'plugin1');
        $hook2 = new TestHook('test2', null, 'plugin2');

        Hook::registerMany([$hook1, $hook2]);

        $this->artisan('hook:flows:list', ['--plugin' => 'plugin1'])
            ->expectsTable(
                ['Identifier', 'Description', 'Plugin', 'Parameters', 'Trigger Point'],
                [[$hook1->getIdentifier(), $hook1->getDescription(), $hook1->getPlugin(), 'param1: string, param2: int', $hook1->getTriggerPoint() ?: '<none>']]
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_shows_warning_when_no_hooks_found(): void
    {
        $this->artisan('hook:flows:list')
            ->expectsOutput('No hooks found.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_shows_warning_when_no_hooks_found_for_plugin(): void
    {
        $this->artisan('hook:flows:list', ['--plugin' => 'non-existent'])
            ->expectsOutput('No hooks found.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_filter_by_plugin_in_json_format(): void
    {
        $hook1 = new TestHook('test1', null, 'plugin1');
        $hook2 = new TestHook('test2', null, 'plugin2');

        Hook::registerMany([$hook1, $hook2]);

        $this->artisan('hook:flows:list', [
            '--plugin' => 'plugin1',
            '--json' => true
        ])
            ->expectsOutput(json_encode([
                [
                    'identifier' => $hook1->getIdentifier(),
                    'description' => $hook1->getDescription(),
                    'plugin' => $hook1->getPlugin(),
                    'parameters' => $hook1->getParameters(),
                    'trigger_point' => $hook1->getTriggerPoint()
                ]
            ], JSON_PRETTY_PRINT))
            ->assertSuccessful();
    }

    #[Test]
    public function it_displays_hook_with_no_parameters(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function getParameters(): array
            {
                return [];
            }
        };
        Hook::register($hook);

        $this->artisan('hook:flows:list')
            ->expectsTable(
                ['Identifier', 'Description', 'Plugin', 'Parameters', 'Trigger Point'],
                [[$hook->getIdentifier(), $hook->getDescription(), $hook->getPlugin(), '<none>', $hook->getTriggerPoint() ?: '<none>']]
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_displays_hook_with_trigger_point(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function getTriggerPoint(): string
            {
                return 'before:action';
            }
        };
        Hook::register($hook);

        $this->artisan('hook:flows:list')
            ->expectsTable(
                ['Identifier', 'Description', 'Plugin', 'Parameters', 'Trigger Point'],
                [[$hook->getIdentifier(), $hook->getDescription(), $hook->getPlugin(), 'param1: string, param2: int', 'before:action']]
            )
            ->assertSuccessful();
    }

    #[Test]
    public function it_displays_multiple_hooks_in_table_format(): void
    {
        $hook1 = new TestHook('test1', 'Description 1', 'plugin1');
        $hook2 = new TestHook('test2', 'Description 2', 'plugin2');

        Hook::registerMany([$hook1, $hook2]);

        $this->artisan('hook:flows:list')
            ->expectsTable(
                ['Identifier', 'Description', 'Plugin', 'Parameters', 'Trigger Point'],
                [
                    [$hook1->getIdentifier(), $hook1->getDescription(), $hook1->getPlugin(), 'param1: string, param2: int', $hook1->getTriggerPoint() ?: '<none>'],
                    [$hook2->getIdentifier(), $hook2->getDescription(), $hook2->getPlugin(), 'param1: string, param2: int', $hook2->getTriggerPoint() ?: '<none>']
                ]
            )
            ->assertSuccessful();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Hook::clear();
    }
}
