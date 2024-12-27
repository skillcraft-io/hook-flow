<?php

namespace Skillcraft\HookFlow\Support;

use Illuminate\Support\Collection;
use Skillcraft\HookFlow\HookDefinition;
use Skillcraft\HookFlow\Tests\Doubles\InvalidTestHook;
use Skillcraft\HookFlow\Support\HookExecutor;

class HookRegistry
{
    /**
     * The registered hooks.
     *
     * @var array<string, array<HookDefinition>>
     */
    private array $hooks = [];

    /**
     * The hook executor.
     */
    private HookExecutor $executor;

    public function __construct(HookExecutor $executor)
    {
        $this->executor = $executor;
    }

    /**
     * Register a new hook definition.
     */
    public function register(HookDefinition $hook): void
    {
        // Always validate hooks before registration
        $hook->validateOrFail();

        $identifier = $hook->getIdentifier();
        if (!isset($this->hooks[$identifier])) {
            $this->hooks[$identifier] = [];
        }
        $this->hooks[$identifier][] = $hook;

        // Sort hooks by priority after adding new one (higher priority first)
        usort($this->hooks[$identifier], function (HookDefinition $a, HookDefinition $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    /**
     * Register multiple hook definitions.
     *
     * @param  array<HookDefinition>  $hooks
     * @throws \InvalidArgumentException if any hook is invalid
     */
    public function registerMany(array $hooks): void
    {
        // Validate all hooks before registering any
        foreach ($hooks as $hook) {
            $hook->validateOrFail();
        }

        // If all hooks are valid, register them
        foreach ($hooks as $hook) {
            $this->register($hook);
        }
    }

    /**
     * Get a specific hook by its identifier.
     */
    public function get(string $identifier): ?HookDefinition
    {
        return $this->hooks[$identifier][0] ?? null;
    }

    /**
     * Get all hooks for a specific identifier.
     *
     * @return Collection<HookDefinition>
     */
    public function getAll(string $identifier): Collection
    {
        return collect($this->hooks[$identifier] ?? []);
    }

    /**
     * Execute a hook with the given arguments.
     *
     * @param string $identifier The hook identifier
     * @param array $args The arguments to pass to the hook
     * @return mixed The result of the hook execution
     * @throws \InvalidArgumentException If the hook is not found
     */
    public function execute(string $identifier, array $args)
    {
        $hooks = $this->getAll($identifier);
        
        if ($hooks->isEmpty()) {
            throw new \InvalidArgumentException("Hook '{$identifier}' not found");
        }

        $result = $args['value'] ?? null;

        // Sort hooks by priority (higher priority first)
        $sortedHooks = $hooks->sortByDesc(function (HookDefinition $hook) {
            return $hook->getPriority();
        });

        foreach ($sortedHooks as $hook) {
            if ($hook->isFilter()) {
                $result = $hook->apply($result, $args);
            } else {
                $hook->execute($args);
            }
        }

        return $result;
    }

    /**
     * Check if a hook exists.
     */
    public function has(string $identifier): bool
    {
        return isset($this->hooks[$identifier]) && !empty($this->hooks[$identifier]);
    }

    /**
     * Get all registered hooks.
     *
     * @return Collection<HookDefinition>
     */
    public function all(): Collection
    {
        return collect($this->hooks)->flatten();
    }

    /**
     * Get hooks by plugin.
     *
     * @return Collection<string, HookDefinition>
     */
    public function forPlugin(string $plugin): Collection
    {
        return $this->all()->filter(
            fn (HookDefinition $hook) => $hook->getPlugin() === $plugin
        );
    }

    /**
     * Get all filter hooks.
     *
     * @return Collection<string, HookDefinition>
     */
    public function filters(): Collection
    {
        return $this->all()->filter(
            fn (HookDefinition $hook) => $hook->isFilter()
        );
    }

    /**
     * Get all action hooks.
     *
     * @return Collection<string, HookDefinition>
     */
    public function actions(): Collection
    {
        return $this->all()->filter(
            fn (HookDefinition $hook) => !$hook->isFilter()
        );
    }

    /**
     * Remove a hook from the registry.
     */
    public function remove(string $identifier): void
    {
        unset($this->hooks[$identifier]);
    }

    /**
     * Clear all registered hooks.
     */
    public function clear(): void
    {
        $this->hooks = [];
    }
}
