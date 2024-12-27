<?php

namespace Skillcraft\HookFlow\Console\Commands;

use Illuminate\Console\Command;
use Skillcraft\HookFlow\Facades\Hook;

class DocumentHooksCommand extends Command
{
    protected $signature = 'hook:flows:document
                            {format=markdown : The output format (markdown or html)}
                            {--output= : The output file path}
                            {--group-by=none : Group hooks by plugin or none}';

    protected $description = 'Generate documentation for all registered hooks';

    public function handle(): int
    {
        $hooks = Hook::all();
        $format = $this->argument('format');
        $output = $this->option('output');
        $groupBy = $this->option('group-by');

        if ($hooks->isEmpty()) {
            $this->line('No hooks found to document.');
            return Command::SUCCESS;
        }

        if ($groupBy === 'plugin') {
            $hooks = $hooks->groupBy(function ($hook) {
                return $hook->getPlugin();
            });
        }

        $documentation = $format === 'html' 
            ? $this->generateHtml($hooks, $groupBy === 'plugin')
            : $this->generateMarkdown($hooks, $groupBy === 'plugin');

        if ($output) {
            $dir = dirname($output);
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($output, $documentation);
            $this->info("Documentation saved to {$output}");
        } else {
            $this->line($documentation);
        }

        return Command::SUCCESS;
    }

    private function generateMarkdown($hooks, bool $grouped): string
    {
        $markdown = "# Hook Documentation\n\n";

        if ($grouped) {
            foreach ($hooks as $plugin => $pluginHooks) {
                $markdown .= "## {$plugin}\n\n";
                foreach ($pluginHooks as $hook) {
                    $markdown .= $this->generateHookMarkdown($hook);
                }
            }
        } else {
            foreach ($hooks as $hook) {
                $markdown .= $this->generateHookMarkdown($hook);
            }
        }

        return $markdown;
    }

    private function generateHookMarkdown($hook): string
    {
        $markdown = "## {$hook->getIdentifier()}\n\n";
        $markdown .= "**Plugin:** {$hook->getPlugin()}\n\n";
        $markdown .= "**Description:** {$hook->getDescription()}\n\n";
        $markdown .= "**Type:** " . ($hook->isFilter() ? 'Filter' : 'Action') . "\n\n";
        $markdown .= "**Priority:** {$hook->getPriority()}\n\n";
        $markdown .= "**Trigger Point:** {$hook->getTriggerPoint()}\n\n";

        $parameters = $hook->getParameters();
        if (!empty($parameters)) {
            $markdown .= "### Parameters\n\n";
            foreach ($parameters as $name => $type) {
                $markdown .= "- `{$name}`: {$type}\n";
            }
            $markdown .= "\n";
        }

        $markdown .= "### Class Reference\n\n";
        $markdown .= "```php\n" . get_class($hook) . "\n```\n\n";
        $markdown .= "---\n\n";

        return $markdown;
    }

    private function generateHtml($hooks, bool $grouped): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<title>Hook Documentation</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }\n";
        $html .= "h1 { color: #333; }\n";
        $html .= "h2 { color: #666; margin-top: 30px; }\n";
        $html .= "code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }\n";
        $html .= "hr { margin: 30px 0; border: none; border-top: 1px solid #eee; }\n";
        $html .= "</style>\n</head>\n<body>\n";
        $html .= "<h1>Hook Documentation</h1>\n";

        if ($grouped) {
            foreach ($hooks as $plugin => $pluginHooks) {
                $html .= "<h2>{$plugin}</h2>\n";
                foreach ($pluginHooks as $hook) {
                    $html .= $this->generateHookHtml($hook);
                }
            }
        } else {
            foreach ($hooks as $hook) {
                $html .= $this->generateHookHtml($hook);
            }
        }

        $html .= "</body>\n</html>";
        return $html;
    }

    private function generateHookHtml($hook): string
    {
        $html = "<h2>{$hook->getIdentifier()}</h2>\n";
        $html .= "<p><strong>Plugin:</strong> {$hook->getPlugin()}</p>\n";
        $html .= "<p><strong>Description:</strong> {$hook->getDescription()}</p>\n";
        $html .= "<p><strong>Type:</strong> " . ($hook->isFilter() ? 'Filter' : 'Action') . "</p>\n";
        $html .= "<p><strong>Priority:</strong> {$hook->getPriority()}</p>\n";
        $html .= "<p><strong>Trigger Point:</strong> {$hook->getTriggerPoint()}</p>\n";

        $parameters = $hook->getParameters();
        if (!empty($parameters)) {
            $html .= "<h3>Parameters</h3>\n<ul>\n";
            foreach ($parameters as $name => $type) {
                $html .= "<li><code>{$name}</code>: {$type}</li>\n";
            }
            $html .= "</ul>\n";
        }

        $html .= "<h3>Class Reference</h3>\n";
        $html .= "<pre><code>" . get_class($hook) . "</code></pre>\n";
        $html .= "<hr>\n";

        return $html;
    }
}
