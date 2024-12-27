<?php

namespace Skillcraft\HookFlow\Facades;

use Illuminate\Support\Facades\Facade;
use Skillcraft\HookFlow\Support\HookRegistry;

/**
 * @method static void register(\Skillcraft\HookFlow\HookDefinition $hook)
 * @method static void registerMany(array $hooks)
 * @method static \Skillcraft\HookFlow\HookDefinition|null get(string $identifier)
 * @method static \Illuminate\Support\Collection all()
 * @method static \Illuminate\Support\Collection forPlugin(string $plugin)
 * @method static void remove(string $identifier)
 * @method static void clear()
 * @method static bool has(string $identifier)
 * @method static mixed execute(string $identifier, array $args)
 */
class Hook extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'hook';
    }
}
