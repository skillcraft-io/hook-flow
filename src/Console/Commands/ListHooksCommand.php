<?php

namespace Skillcraft\HookFlow\Console\Commands;

use Illuminate\Console\Command;
use Skillcraft\HookFlow\Facades\Hook;

class ListHooksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hook:flows:list
                            {--json : Output in JSON format}
                            {--plugin= : Filter hooks by plugin name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered hooks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hooks = Hook::all();

        if ($plugin = $this->option('plugin')) {
            $hooks = $hooks->filter(function ($hook) use ($plugin) {
                return $hook->getPlugin() === $plugin;
            });
        }

        if ($hooks->isEmpty()) {
            $this->line('No hooks found.');
            return Command::SUCCESS;
        }

        if ($this->option('json')) {
            $output = $hooks->map(function ($hook) {
                return [
                    'identifier' => $hook->getIdentifier(),
                    'description' => $hook->getDescription(),
                    'plugin' => $hook->getPlugin(),
                    'parameters' => $hook->getParameters(),
                    'trigger_point' => $hook->getTriggerPoint()
                ];
            })->values()->all();

            $this->line(json_encode($output, JSON_PRETTY_PRINT));
        } else {
            $headers = ['Identifier', 'Description', 'Plugin', 'Parameters', 'Trigger Point'];
            $rows = [];

            foreach ($hooks as $hook) {
                $parameters = $hook->getParameters();
                $paramStr = empty($parameters) 
                    ? '<none>'
                    : implode(', ', array_map(function($name, $type) {
                        return "{$name}: {$type}";
                    }, array_keys($parameters), array_values($parameters)));

                $rows[] = [
                    $hook->getIdentifier(),
                    $hook->getDescription(),
                    $hook->getPlugin(),
                    $paramStr,
                    $hook->getTriggerPoint() ?: '<none>'
                ];
            }

            $this->table($headers, $rows);
        }

        return Command::SUCCESS;
    }
}
