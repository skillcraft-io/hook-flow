<?php

namespace Skillcraft\HookFlow;

use Illuminate\Support\Collection;
use InvalidArgumentException;

abstract class HookDefinition
{
    /**
     * Get the unique identifier for the hook.
     * By default, uses the fully qualified class name.
     * Can be overridden in child classes for custom identifiers.
     */
    public function getIdentifier(): string
    {
        return static::class;
    }

    /**
     * Get the description of the hook.
     */
    abstract public function getDescription(): string;

    /**
     * Get the plugin that owns this hook.
     */
    abstract public function getPlugin(): string;

    /**
     * Get the parameters that are passed to this hook.
     *
     * @return array<string, string> Array of parameter names and their types
     */
    abstract public function getParameters(): array;

    /**
     * Get when this hook is triggered.
     */
    abstract public function getTriggerPoint(): string;

    /**
     * Execute the hook logic for an action.
     * This method should be implemented by action hooks.
     *
     * @param array $args The arguments passed to the hook
     */
    public function execute(array $args): void
    {
        // Default implementation for actions
        // Override this in your hook class
    }

    /**
     * Apply the hook logic for a filter.
     * This method should be implemented by filter hooks.
     *
     * @param mixed $value The value to filter
     * @param array $args All arguments passed to the hook
     * @return mixed The filtered value
     */
    public function apply($value, array $args)
    {
        // Default implementation for filters
        // Override this in your hook class
        return $value;
    }

    /**
     * Get the priority of the hook.
     * Lower numbers indicate higher priority.
     * Default is 10.
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * Get the number of accepted arguments.
     * Default is the number of parameters defined.
     */
    public function getAcceptedArgs(): int
    {
        return count($this->getParameters());
    }

    /**
     * Determine if this hook is a filter.
     * Filters modify and return values, while actions perform operations.
     */
    public function isFilter(): bool
    {
        return false;
    }

    /**
     * Validate the hook definition.
     *
     * @return Collection<string>
     */
    public function validate(): Collection
    {
        $issues = collect();

        // Validate identifier
        if (empty($this->getIdentifier())) {
            $issues->push('Hook identifier cannot be empty');
        } elseif (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\\\\\-]*$/', $this->getIdentifier())) {
            $issues->push('Hook identifier should only contain letters, numbers, underscores, backslashes, and hyphens');
        }

        // Validate description
        if (empty($this->getDescription())) {
            $issues->push('Hook description cannot be empty');
        }

        // Validate plugin name
        if (empty($this->getPlugin())) {
            $issues->push('Hook plugin cannot be empty');
        }

        // Validate parameters
        foreach ($this->getParameters() as $name => $type) {
            if (empty($name)) {
                $issues->push('Parameter name cannot be empty');
            }
            if (empty($type)) {
                $issues->push("Parameter '{$name}' has no type specified");
            }
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
                $issues->push("Invalid parameter name '{$name}'");
            }
        }

        // Validate priority
        if ($this->getPriority() < 1) {
            $issues->push('Hook priority must be greater than 0');
        }

        return $issues;
    }

    /**
     * Validate the hook definition and throw an exception if invalid.
     *
     * @throws InvalidArgumentException
     */
    public function validateOrFail(): void
    {
        $issues = $this->validate();
        if ($issues->isNotEmpty()) {
            throw new InvalidArgumentException(implode("\n", $issues->all()));
        }
    }
}
