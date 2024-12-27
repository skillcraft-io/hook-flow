<?php

namespace Skillcraft\HookFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Skillcraft\HookFlow\Facades\Hook;
use Skillcraft\HookFlow\HookDefinition;
use Symfony\Component\Finder\Finder;

class DiscoverHooksCommand extends Command
{
    protected $signature = 'hook:flows:discover
                            {directories* : The directories to scan for hooks}
                            {--json : Output in JSON format}';

    protected $description = 'Discover hooks in the specified directories';

    public function handle(): int
    {
        $directories = $this->argument('directories');
        $hooks = new Collection();

        foreach ($directories as $directory) {
            $hooks = $hooks->merge($this->findHooksInDirectory($directory));
        }

        // Register all discovered hooks
        $hooks->each(function (HookDefinition $hook) {
            Hook::register($hook);
        });

        $discovered = $hooks->map(function (HookDefinition $hook) {
            return [
                'identifier' => $hook->getIdentifier(),
                'class' => get_class($hook)
            ];
        })->values();

        if ($discovered->isEmpty()) {
            if ($this->option('json')) {
                $this->getOutput()->write(json_encode([
                    'discovered' => []
                ], JSON_PRETTY_PRINT));
            } else {
                $this->warn('No hooks found.');
            }
            return self::SUCCESS;
        }

        if ($this->option('json')) {
            $this->getOutput()->write(json_encode([
                'discovered' => $discovered->all()
            ], JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->info('Discovered Hooks:');
        $discovered->each(function ($hook) {
            $this->line(sprintf('  - %s (%s)', $hook['identifier'], $hook['class']));
        });

        return self::SUCCESS;
    }

    private function findHooksInDirectory(string $directory): Collection
    {
        $hooks = new Collection();
        $directory = realpath($directory);

        if (!$directory || !is_dir($directory)) {
            if (!$this->option('json')) {
                $this->error(sprintf('Directory %s does not exist', $directory));
            }
            return $hooks;
        }

        if (!$this->option('json')) {
            $this->info(sprintf('Scanning directory: %s', $directory));
        }

        $finder = new Finder();
        $finder->files()->in($directory)->name('*.php')->sortByName();

        if (!$this->option('json')) {
            $this->info(sprintf('Found %d PHP files in directory', iterator_count($finder)));
        }

        $seenIdentifiers = [];

        foreach ($finder as $file) {
            if (!$this->option('json')) {
                $this->info(sprintf('Processing file: %s', $file->getRealPath()));
            }

            try {
                // Include the file to make the class available
                $code = file_get_contents($file->getRealPath());
                if (strpos($code, '<?php') === false) {
                    throw new \ParseError('File does not start with <?php');
                }
                
                ob_start();
                include_once $file->getRealPath();
                ob_end_clean();

                // Get all declared classes
                $declaredClasses = get_declared_classes();

                // Find classes that are hook definitions
                foreach ($declaredClasses as $class) {
                    if (is_subclass_of($class, HookDefinition::class)) {
                        $reflection = new \ReflectionClass($class);
                        if ($reflection->getFileName() === $file->getRealPath()) {
                            $instance = new $class();

                            // Skip if we've already seen this identifier
                            $identifier = $instance->getIdentifier();
                            if (isset($seenIdentifiers[$identifier])) {
                                if (!$this->option('json')) {
                                    $this->error(sprintf('Skipping duplicate hook identifier "%s" from %s', $identifier, $class));
                                }
                                continue;
                            }

                            $hooks->push($instance);
                            $seenIdentifiers[$identifier] = true;

                            if (!$this->option('json')) {
                                $this->info(sprintf('Added hook: %s', $class));
                            }
                        }
                    }
                }
            } catch (\ParseError $e) {
                if (!$this->option('json')) {
                    $this->error(sprintf('Failed to parse file %s: %s', $file->getRealPath(), $e->getMessage()));
                }
                continue;
            } catch (\Exception $e) {
                if (!$this->option('json')) {
                    $this->error(sprintf('Failed to process file %s: %s', $file->getRealPath(), $e->getMessage()));
                }
                continue;
            }
        }

        if (!$this->option('json')) {
            $this->info(sprintf('Found %d hooks in directory', $hooks->count()));
        }

        return $hooks;
    }
}
