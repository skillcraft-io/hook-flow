<?php

namespace Skillcraft\HookFlow\Tests\Doubles;

use Skillcraft\HookFlow\HookDefinition;

class InvalidTestHook extends HookDefinition
{
    private string $identifier;
    private string $description;
    private string $plugin;
    private array $parameters;
    private string $triggerPoint;

    public function __construct(
        string $identifier = '',
        string $description = '',
        string $plugin = '',
        array $parameters = [],
        string $triggerPoint = ''
    ) {
        $this->identifier = $identifier;
        $this->description = $description;
        $this->plugin = $plugin;
        $this->parameters = $parameters;
        $this->triggerPoint = $triggerPoint;
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

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getTriggerPoint(): string
    {
        return $this->triggerPoint;
    }
}
