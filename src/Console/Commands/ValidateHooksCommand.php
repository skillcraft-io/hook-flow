<?php

namespace Skillcraft\HookFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Skillcraft\HookFlow\HookDefinition;
use Skillcraft\HookFlow\Support\HookRegistry;
use Skillcraft\HookFlow\Facades\Hook;

class ValidateHooksCommand extends Command
{
    protected $signature = 'hook:flows:validate
                            {directories?* : The directories to scan for hooks}
                            {--json : Output in JSON format}';

    protected $description = 'Validate hooks in the specified directories';

    private array $identifierClasses = [];

    public function handle(): int
    {
        $directories = $this->argument('directories');
        $useJson = $this->option('json');

        $results = [
            'valid' => [],
            'invalid' => [],
            'duplicates' => [],
            'errors' => [],
        ];

        if (!empty($directories)) {
            $this->validateDirectories($directories, $results);
        } else {
            $this->validateRegisteredHooks($results);
        }

        // Process duplicates without modifying the valid list
        $this->processDuplicates($results);

        if ($useJson) {
            // In JSON mode, remove duplicates from valid list
            foreach ($results['duplicates'] as $duplicate) {
                $results['valid'] = array_values(array_filter($results['valid'], function ($item) use ($duplicate) {
                    return $item['identifier'] !== $duplicate['identifier'];
                }));
            }
            $this->outputJson($results);
        } else {
            $this->outputText($results);
        }

        // Return success if:
        // 1. No hooks were found at all
        // 2. We have valid hooks (duplicates are allowed)
        $foundHooks = !empty($results['valid']) || !empty($results['invalid']);
        return !$foundHooks || !empty($results['valid']) ? Command::SUCCESS : Command::FAILURE;
    }

    private function validateDirectories(array $directories, array &$results): void
    {
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                $results['errors'][] = "Directory not found: {$directory}";
                continue;
            }

            $this->validateHooksInDirectory($directory, $results);
        }
    }

    private function validateHooksInDirectory(string $directory, array &$results): void
    {
        $files = glob($directory . '/*.php');
        foreach ($files as $file) {
            try {
                require_once $file;
                $className = $this->getClassNameFromFile($file);
                if (!$className || !class_exists($className)) {
                    $results['errors'][] = "Failed to process file {$file}";
                    continue;
                }

                try {
                    $hook = new $className();
                    if (!$hook instanceof HookDefinition) {
                        continue;
                    }

                    $this->validateHook($hook, $results);
                } catch (\Throwable $e) {
                    $results['errors'][] = "Failed to process file {$file}: {$e->getMessage()}";
                    continue;
                }
            } catch (\Throwable $e) {
                $results['errors'][] = "Failed to process file {$file}: {$e->getMessage()}";
                continue;
            }
        }
    }

    private function validateRegisteredHooks(array &$results): void
    {
        $hooks = Hook::all();
        if ($hooks->isEmpty()) {
            if (!$this->option('json')) {
                $this->line('No hooks registered.');
            }
            return;
        }

        foreach ($hooks as $hook) {
            try {
                $this->validateHook($hook, $results);
            } catch (\Throwable $e) {
                $results['errors'][] = "Failed to validate hook {$hook}: {$e->getMessage()}";
            }
        }
    }

    private function validateHook(HookDefinition $hook, array &$results): void
    {
        try {
            $class = get_class($hook);
            $identifier = $hook->getIdentifier();
            $issues = $hook->validate();

            if (empty($identifier)) {
                $results['invalid'][] = [
                    'identifier' => '',
                    'class' => $class,
                    'issues' => ['Hook identifier cannot be empty'],
                ];
                return;
            }

            if ($issues->isEmpty()) {
                // Track classes by identifier for duplicate detection
                if (!isset($this->identifierClasses[$identifier])) {
                    $this->identifierClasses[$identifier] = [];
                }
                $this->identifierClasses[$identifier][] = $class;

                $results['valid'][] = [
                    'identifier' => $identifier,
                    'class' => $class,
                ];
            } else {
                $results['invalid'][] = [
                    'identifier' => $identifier,
                    'class' => $class,
                    'issues' => $issues->toArray(),
                ];
            }
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function processDuplicates(array &$results): void
    {
        foreach ($this->identifierClasses as $identifier => $classes) {
            if (count($classes) > 1) {
                $results['duplicates'][] = [
                    'identifier' => $identifier,
                    'classes' => $classes,
                ];

                // Remove duplicates from valid list in JSON mode
                if ($this->option('json')) {
                    $results['valid'] = array_values(array_filter($results['valid'], function ($item) use ($identifier) {
                        return $item['identifier'] !== $identifier;
                    }));
                }
            }
        }
    }

    private function outputJson(array $results): void
    {
        $this->line(json_encode($results, JSON_PRETTY_PRINT));
    }

    private function outputText(array $results): void
    {
        if (!empty($results['valid'])) {
            $this->info('Valid Hooks:');
            foreach ($results['valid'] as $hook) {
                $this->line("  - {$hook['identifier']}");
            }
        }

        if (!empty($results['invalid'])) {
            $this->error('Invalid Hooks:');
            foreach ($results['invalid'] as $hook) {
                $this->line("  - {$hook['class']}:");
                foreach ($hook['issues'] as $issue) {
                    $this->line("    - {$issue}");
                }
            }
        }

        if (!empty($results['duplicates'])) {
            $this->warn('Duplicate Hook Identifiers:');
            foreach ($results['duplicates'] as $duplicate) {
                $this->line("  - \"{$duplicate['identifier']}\" is used by:");
                foreach ($duplicate['classes'] as $class) {
                    $this->line("    - {$class}");
                }
            }
        }

        if (!empty($results['errors'])) {
            $this->error('Errors:');
            foreach ($results['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = '';
        $class = '';
        $namespaceFound = false;
        $classFound = false;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespaceFound = true;
                continue;
            }

            if ($namespaceFound && ($token[0] === T_STRING || $token[0] === T_NAME_QUALIFIED)) {
                $namespace = $token[1];
                $namespaceFound = false;
                continue;
            }

            if ($token[0] === T_CLASS) {
                $classFound = true;
                continue;
            }

            if ($classFound && $token[0] === T_STRING) {
                $class = $token[1];
                break;
            }
        }

        return $namespace && $class ? $namespace . '\\' . $class : null;
    }
}
