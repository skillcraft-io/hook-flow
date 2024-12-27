<?php

namespace Skillcraft\HookFlow\Tests\Doubles;

use Skillcraft\HookFlow\HookDefinition;
use Illuminate\Support\Collection;

class TestHook extends HookDefinition
{
    private string $identifier;
    private string $description;
    private string $plugin;
    private array $parameters;
    private string $triggerPoint;
    private int $priority;

    public function __construct(
        string|null $identifier = 'test-hook',
        string|null $description = null,
        string $plugin = 'test-plugin',
        array|null $parameters = null,
        string $triggerPoint = 'test@test:before',
        int $priority = 10
    ) {
        $this->identifier = $identifier;
        $this->description = $description ?? 'Test hook for testing purposes';
        $this->plugin = $plugin;
        $this->parameters = $parameters ?? ['param1' => 'string', 'param2' => 'int'];
        $this->triggerPoint = $triggerPoint;
        $this->priority = $priority;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPlugin(): string
    {
        return $this->plugin;
    }

    public function getTriggerPoint(): string
    {
        return $this->triggerPoint;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function execute(array $args): void
    {
        // Do nothing
    }

    public function isFilter(): bool
    {
        return false;
    }

    public function apply($value, array $args): mixed
    {
        return $value;
    }
}
