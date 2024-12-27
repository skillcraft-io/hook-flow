<?php

namespace Skillcraft\HookFlow\Tests\Unit\Console\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Console\Kernel;
use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Facades\Hook;
use Skillcraft\HookFlow\Tests\TestCase;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use Skillcraft\HookFlow\Console\Commands\DiscoverHooksCommand;

class DiscoverHooksCommandTest extends TestCase
{
    private string $tempDir;
    private int $hookCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Hook::clear();
        $this->tempDir = sys_get_temp_dir() . '/test-hooks-' . uniqid();
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0777, true);
        }
    }

    private function createTestHook(string $tempDir): string
    {
        $this->hookCounter++;
        $className = 'TestHook' . $this->hookCounter . '_' . uniqid();
        $namespace = 'Skillcraft\HookFlow\Tests\Doubles\Temp' . $this->hookCounter;
        
        file_put_contents($tempDir . '/' . $className . '.php', '<?php
            namespace ' . $namespace . ';
            use Skillcraft\HookFlow\HookDefinition;
            class ' . $className . ' extends HookDefinition {
                public function getIdentifier(): string { return "test-hook"; }
                public function getDescription(): string { return "desc"; }
                public function getPlugin(): string { return "plugin"; }
                public function getParameters(): array { 
                    return [
                        "param1" => "string"
                    ]; 
                }
                public function getTriggerPoint(): string { return "trigger"; }
            }
        ');
        return $namespace . '\\' . $className;
    }

    private function createTempDir(): string
    {
        $tempDir = sys_get_temp_dir() . '/hook_test_' . uniqid();
        mkdir($tempDir);
        return $tempDir;
    }

    private function cleanup(string $tempDir, array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($tempDir . '/' . $file . '.php')) {
                unlink($tempDir . '/' . $file . '.php');
            }
        }
        if (is_dir($tempDir)) {
            rmdir($tempDir);
        }
    }

    #[Test]
    public function it_can_discover_hooks_from_directory(): void
    {
        $tempDir = $this->createTempDir();
        $className = $this->createTestHook($tempDir);

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutputToContain('Discovered Hooks:')
            ->expectsOutputToContain('  - test-hook (' . $className . ')')
            ->assertSuccessful();

        $this->assertTrue(Hook::has('test-hook'));

        $this->cleanup($tempDir, [explode('\\', $className)[5]]);
    }

    #[Test]
    public function it_can_output_json(): void
    {
        $tempDir = $this->createTempDir();
        $className = $this->createTestHook($tempDir);

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir],
            '--json' => true
        ])
            ->assertSuccessful();

        $this->cleanup($tempDir, [explode('\\', $className)[5]]);
    }

    #[Test]
    public function it_shows_warning_when_no_hooks_found(): void
    {
        $tempDir = $this->createTempDir();

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutput('No hooks found.')
            ->assertSuccessful();

        $this->cleanup($tempDir, []);
    }

    #[Test]
    public function it_handles_invalid_php_files(): void
    {
        $tempDir = $this->createTempDir();
        file_put_contents($tempDir . '/invalid.php', 'not even a php file');

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutputToContain('Failed to parse file ' . $tempDir . '/invalid.php')
            ->expectsOutput('No hooks found.')
            ->assertSuccessful();

        $this->cleanup($tempDir, ['invalid']);
    }

    #[Test]
    public function it_handles_non_hook_classes(): void
    {
        $tempDir = $this->createTempDir();
        $this->hookCounter++;
        $className = 'NonHook' . $this->hookCounter . '_' . uniqid();
        $namespace = 'Skillcraft\HookFlow\Tests\Doubles\Temp' . $this->hookCounter;
        
        file_put_contents($tempDir . '/' . $className . '.php', '<?php
            namespace ' . $namespace . ';
            class ' . $className . ' {
                public function foo() {}
            }
        ');

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutput('No hooks found.')
            ->assertSuccessful();

        $this->cleanup($tempDir, [$className]);
    }

    #[Test]
    public function it_handles_hook_instantiation_error(): void
    {
        $tempDir = $this->createTempDir();
        $this->hookCounter++;
        $className = 'ErrorHook' . $this->hookCounter . '_' . uniqid();
        $namespace = 'Skillcraft\HookFlow\Tests\Doubles\Temp' . $this->hookCounter;
        
        file_put_contents($tempDir . '/' . $className . '.php', '<?php
            namespace ' . $namespace . ';
            use Skillcraft\HookFlow\HookDefinition;
            class ' . $className . ' extends HookDefinition {
                public function __construct() {
                    throw new \Exception("Test exception");
                }
                public function getIdentifier(): string { return "test-hook"; }
                public function getDescription(): string { return "desc"; }
                public function getPlugin(): string { return "plugin"; }
                public function getParameters(): array { 
                    return [
                        "param1" => "string"
                    ]; 
                }
                public function getTriggerPoint(): string { return "trigger"; }
            }
        ');

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutputToContain('Failed to process file ' . $tempDir . '/' . $className . '.php: Test exception')
            ->expectsOutputToContain('No hooks found.')
            ->assertSuccessful();

        $this->cleanup($tempDir, [$className]);
    }

    #[Test]
    public function it_skips_duplicate_hook_identifiers(): void
    {
        $tempDir = $this->createTempDir();
        $this->hookCounter++;
        $class1 = 'DuplicateHook' . $this->hookCounter . '_' . uniqid();
        $namespace1 = 'Skillcraft\HookFlow\Tests\Doubles\Temp' . $this->hookCounter;
        
        $this->hookCounter++;
        $class2 = 'DuplicateHook' . $this->hookCounter . '_' . uniqid();
        $namespace2 = 'Skillcraft\HookFlow\Tests\Doubles\Temp' . $this->hookCounter;

        file_put_contents($tempDir . '/' . $class1 . '.php', '<?php
            namespace ' . $namespace1 . ';
            use Skillcraft\HookFlow\HookDefinition;
            class ' . $class1 . ' extends HookDefinition {
                public function getIdentifier(): string { return "test-hook"; }
                public function getDescription(): string { return "desc"; }
                public function getPlugin(): string { return "plugin"; }
                public function getParameters(): array { 
                    return [
                        "param1" => "string"
                    ]; 
                }
                public function getTriggerPoint(): string { return "trigger"; }
            }
        ');

        file_put_contents($tempDir . '/' . $class2 . '.php', '<?php
            namespace ' . $namespace2 . ';
            use Skillcraft\HookFlow\HookDefinition;
            class ' . $class2 . ' extends HookDefinition {
                public function getIdentifier(): string { return "test-hook"; }
                public function getDescription(): string { return "desc"; }
                public function getPlugin(): string { return "plugin"; }
                public function getParameters(): array { 
                    return [
                        "param1" => "string"
                    ]; 
                }
                public function getTriggerPoint(): string { return "trigger"; }
            }
        ');

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutputToContain('Discovered Hooks:')
            ->expectsOutputToContain('  - test-hook (' . $namespace1 . '\\' . $class1 . ')')
            ->assertSuccessful();

        $this->assertTrue(Hook::has('test-hook'));

        $this->cleanup($tempDir, [$class1, $class2]);
    }

    #[Test]
    public function it_handles_no_hooks_found(): void
    {
        $this->artisan('hook:flows:discover', [
            'directories' => [$this->tempDir]
        ])
            ->expectsOutput('No hooks found.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_find_hooks_in_directory(): void
    {
        $tempDir = $this->createTempDir();
        $className = $this->createTestHook($tempDir);

        $this->artisan('hook:flows:discover', [
            'directories' => [$tempDir]
        ])
            ->expectsOutputToContain('Found 1 hooks in directory')
            ->expectsOutputToContain('test-hook')
            ->assertSuccessful();

        $this->cleanup($tempDir, [explode('\\', $className)[5]]);
    }

    private function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Hook::clear();
        if (file_exists($this->tempDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($this->tempDir);
        }
    }
}
