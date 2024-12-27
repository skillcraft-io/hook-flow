<?php

namespace Skillcraft\HookFlow\Tests\Unit\Console\Commands;

use PHPUnit\Framework\Attributes\Test;
use Skillcraft\HookFlow\Tests\TestCase;
use Skillcraft\HookFlow\Tests\Doubles\TestHook;
use Skillcraft\HookFlow\Facades\Hook;
use Illuminate\Support\Collection;

class ValidateHooksCommandTest extends TestCase
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
        
        // Register autoloader for test classes
        spl_autoload_register(function ($class) {
            if (strpos($class, 'Skillcraft\HookFlow\Tests\Doubles\Temp\\') === 0) {
                $classPath = $this->tempDir . '/' . basename(str_replace('\\', '/', $class)) . '.php';
                if (file_exists($classPath)) {
                    require_once $classPath;
                    return true;
                }
            }
            return false;
        });
    }

    private function createHookClass(string $identifier = 'test-hook', array $methods = []): string
    {
        $this->hookCounter++;
        $className = 'Hook' . uniqid();
        $namespace = 'Skillcraft\HookFlow\Tests\Doubles\Temp';
        $filePath = $this->tempDir . '/' . $className . '.php';

        $defaultMethods = [
            'getDescription' => 'return "desc";',
            'getPlugin' => 'return "plugin";',
            'getParameters' => 'return [];',
            'getTriggerPoint' => 'return "trigger";',
            'validate' => 'return parent::validate();'
        ];

        // Only add getIdentifier if a custom one is specified
        if ($identifier !== 'test-hook') {
            $defaultMethods['getIdentifier'] = 'return "' . $identifier . '";';
        }

        $methods = array_merge($defaultMethods, $methods);
        $methodsCode = '';
        foreach ($methods as $name => $body) {
            $type = match($name) {
                'getParameters' => 'array',
                'validate' => '\Illuminate\Support\Collection',
                default => 'string'
            };
            $methodsCode .= "
    public function {$name}(): {$type} 
    { 
        {$body}
    }";
        }

        $code = '<?php
namespace ' . $namespace . ';

use Skillcraft\HookFlow\HookDefinition;
use Illuminate\Support\Collection;

class ' . $className . ' extends HookDefinition 
{' . $methodsCode . '
}';

        file_put_contents($filePath, $code);
        return $namespace . '\\' . $className;
    }

    #[Test]
    public function it_can_validate_registered_hooks(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->artisan('hook:flows:validate')
            ->expectsOutput('Valid Hooks:')
            ->expectsOutput('  - test-hook')
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_validate_hooks_in_directory(): void
    {
        $this->artisan('hook:flows:validate', [
            'directories' => [__DIR__ . '/../../../Doubles']
        ])
            ->expectsOutput('Valid Hooks:')
            ->expectsOutput('  - test-hook')
            ->assertSuccessful();
    }

    #[Test]
    public function it_can_output_json(): void
    {
        $hook = new TestHook();
        Hook::register($hook);

        $this->artisan('hook:flows:validate', ['--json' => true])
            ->expectsOutput(json_encode([
                'valid' => [
                    [
                        'identifier' => 'test-hook',
                        'class' => TestHook::class
                    ]
                ],
                'invalid' => [],
                'duplicates' => [],
                'errors' => []
            ], JSON_PRETTY_PRINT))
            ->assertSuccessful();
    }

    #[Test]
    public function it_shows_warning_when_no_hooks_found(): void
    {
        $this->artisan('hook:flows:validate', [
            'directories' => [__DIR__ . '/non-existent']
        ])
            ->expectsOutputToContain('Directory not found: ' . __DIR__ . '/non-existent')
            ->assertSuccessful();
    }

    #[Test]
    public function it_detects_duplicate_identifiers(): void
    {
        $hook1 = $this->createHookClass('duplicate-hook');
        $hook2 = $this->createHookClass('duplicate-hook');

        $this->artisan('hook:flows:validate', ['directories' => [$this->tempDir]])
            ->expectsOutput('Duplicate Hook Identifiers:')
            ->expectsOutput('  - "duplicate-hook" is used by:')
            ->assertSuccessful();
    }

    #[Test]
    public function it_outputs_duplicate_identifiers_as_json(): void
    {
        $class1 = $this->createHookClass('duplicate-hook');
        $class2 = $this->createHookClass('duplicate-hook');

        $this->artisan('hook:flows:validate', [
            'directories' => [$this->tempDir],
            '--json' => true
        ])
            ->assertSuccessful()
            ->expectsOutput(json_encode([
                'valid' => [],
                'invalid' => [],
                'duplicates' => [
                    [
                        'identifier' => 'duplicate-hook',
                        'classes' => [
                            $class1,
                            $class2
                        ]
                    ]
                ],
                'errors' => []
            ], JSON_PRETTY_PRINT));
    }

    #[Test]
    public function it_handles_no_hooks(): void
    {
        $this->artisan('hook:flows:validate')
            ->expectsOutputToContain('No hooks registered.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_handles_invalid_directory(): void
    {
        $this->artisan('hook:flows:validate', [
            'directories' => ['/invalid/directory']
        ])
            ->expectsOutputToContain('Directory not found: /invalid/directory')
            ->assertSuccessful();
    }

    #[Test]
    public function it_validates_hooks_with_validation_issues(): void
    {
        $class = $this->createHookClass('invalid-hook', [
            'validate' => 'return collect(["Invalid hook configuration"]);'
        ]);

        $this->artisan('hook:flows:validate', [
            'directories' => [$this->tempDir]
        ])
            ->expectsOutput('Invalid Hooks:')
            ->expectsOutput("  - {$class}:")
            ->expectsOutput('    - Invalid hook configuration')
            ->assertFailed();
    }

    #[Test]
    public function it_outputs_validation_issues_as_json(): void
    {
        $class = $this->createHookClass('invalid-hook', [
            'validate' => 'return collect(["Invalid hook configuration"]);'
        ]);

        $output = [
            'valid' => [],
            'invalid' => [
                [
                    'identifier' => 'invalid-hook',
                    'class' => $class,
                    'issues' => ['Invalid hook configuration']
                ]
            ],
            'duplicates' => [],
            'errors' => []
        ];

        $this->artisan('hook:flows:validate', [
            'directories' => [$this->tempDir],
            '--json' => true
        ])
            ->expectsOutput(json_encode($output, JSON_PRETTY_PRINT))
            ->assertFailed();
    }

    #[Test]
    public function it_handles_invalid_hook_instantiation(): void
    {
        $class = $this->createHookClass('test-hook', [
            'getIdentifier' => 'throw new \Exception("Test exception");'
        ]);

        $this->artisan('hook:flows:validate', [
            'directories' => [$this->tempDir]
        ])
            ->expectsOutputToContain('Test exception')
            ->assertSuccessful();
    }

    #[Test]
    public function it_handles_invalid_php_files(): void
    {
        file_put_contents($this->tempDir . '/InvalidFile.php', '<?php this is malformed php;');

        $this->artisan('hook:flows:validate', [
            'directories' => [$this->tempDir]
        ])
            ->expectsOutputToContain('Failed to process file ' . $this->tempDir . '/InvalidFile.php')
            ->assertSuccessful();
    }

    #[Test]
    public function it_handles_empty_hook_identifier(): void
    {
        $class = $this->createHookClass('', [
            'getDescription' => 'return "";',
            'getPlugin' => 'return "";',
            'getTriggerPoint' => 'return "";'
        ]);

        $this->artisan('hook:flows:validate', [
            'directories' => [$this->tempDir]
        ])
            ->expectsOutput('Invalid Hooks:')
            ->expectsOutput("  - {$class}:")
            ->expectsOutput('    - Hook identifier cannot be empty')
            ->assertFailed();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Hook::clear();
        foreach (glob($this->tempDir . '/*.php') as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }
}
