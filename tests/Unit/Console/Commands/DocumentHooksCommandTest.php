<?php

namespace Skillcraft\HookFlow\Tests\Unit\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Tests\TestCase;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use Skillcraft\HookFlow\Facades\Hook;

class DocumentHooksCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Hook::clear();
    }

    #[Test]
    public function it_can_generate_markdown_documentation(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.md',
            'format' => 'markdown'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.md');
        $this->assertStringContainsString('# Hook Documentation', $content);
        $this->assertStringContainsString('## test-hook', $content);
        $this->assertStringContainsString('**Plugin:** test-plugin', $content);
        $this->assertStringContainsString('### Parameters', $content);
        unlink('hooks.md');
    }

    #[Test]
    public function it_can_generate_html_documentation(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.html',
            'format' => 'html'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.html');
        $this->assertStringContainsString('<h1>Hook Documentation</h1>', $content);
        $this->assertStringContainsString('<h2>', $content);
        $this->assertStringContainsString('<strong>Plugin:</strong>', $content);
        $this->assertStringContainsString('<h3>Parameters</h3>', $content);
        unlink('hooks.html');
    }

    #[Test]
    public function it_shows_warning_when_no_hooks_to_document(): void
    {
        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.md'
        ])
            ->expectsOutput('No hooks found to document.')
            ->assertSuccessful();

        $this->assertFileDoesNotExist('hooks.md');
    }

    #[Test]
    public function it_can_group_hooks_by_plugin(): void
    {
        $hook1 = new TestHook('test1', 'Test hook 1', 'plugin1');
        $hook2 = new TestHook('test2', 'Test hook 2', 'plugin1');
        $hook3 = new TestHook('test3', 'Test hook 3', 'plugin2');

        Hook::registerMany([$hook1, $hook2, $hook3]);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.md',
            'format' => 'markdown',
            '--group-by' => 'plugin'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.md');
        $this->assertStringContainsString('## plugin1', $content);
        $this->assertStringContainsString('## test1', $content);
        $this->assertStringContainsString('## test2', $content);
        $this->assertStringContainsString('## plugin2', $content);
        $this->assertStringContainsString('## test3', $content);
        unlink('hooks.md');
    }

    #[Test]
    public function it_can_generate_docs_without_grouping(): void
    {
        $hook1 = new TestHook('test1', 'Test hook 1', 'plugin1');
        $hook2 = new TestHook('test2', 'Test hook 2', 'plugin2');

        Hook::registerMany([$hook1, $hook2]);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.md',
            'format' => 'markdown',
            '--group-by' => 'none'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.md');
        $this->assertStringContainsString('# Hook Documentation', $content);
        $this->assertStringContainsString('## test1', $content);
        $this->assertStringContainsString('## test2', $content);
        unlink('hooks.md');
    }

    #[Test]
    public function it_can_generate_html_docs_without_grouping(): void
    {
        $hook1 = new TestHook('test1', 'Test hook 1', 'plugin1');
        $hook2 = new TestHook('test2', 'Test hook 2', 'plugin2');

        Hook::registerMany([$hook1, $hook2]);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.html',
            'format' => 'html',
            '--group-by' => 'none'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.html');
        $this->assertStringContainsString('<h1>Hook Documentation</h1>', $content);
        $this->assertStringContainsString('<h2>', $content);
        $this->assertStringContainsString('>test1<', $content);
        $this->assertStringContainsString('>test2<', $content);
        unlink('hooks.html');
    }

    #[Test]
    public function it_creates_output_directory_if_not_exists(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $outputDir = 'test-output';
        $outputPath = $outputDir . '/hooks.md';

        $this->artisan('hook:flows:document', [
            '--output' => $outputPath
        ])->assertSuccessful();

        $this->assertFileExists($outputPath);
        unlink($outputPath);
        rmdir($outputDir);
    }

    #[Test]
    public function it_includes_trigger_point_in_markdown_docs(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function getTriggerPoint(): string
            {
                return 'before:action';
            }
        };
        Hook::register($hook);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.md',
            'format' => 'markdown'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.md');
        $this->assertStringContainsString('**Trigger Point:** before:action', $content);
        unlink('hooks.md');
    }

    #[Test]
    public function it_includes_trigger_point_in_html_docs(): void
    {
        $hook = new class('test-hook') extends TestHook {
            public function getTriggerPoint(): string
            {
                return 'before:action';
            }
        };
        Hook::register($hook);

        $this->artisan('hook:flows:document', [
            '--output' => 'hooks.html',
            'format' => 'html'
        ])->assertSuccessful();

        $content = file_get_contents('hooks.html');
        $this->assertStringContainsString('<strong>Trigger Point:</strong> before:action', $content);
        unlink('hooks.html');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Hook::clear();
    }
}
