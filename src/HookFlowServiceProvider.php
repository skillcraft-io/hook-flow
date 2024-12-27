<?php

namespace Skillcraft\HookFlow;

use Illuminate\Support\ServiceProvider;
use Skillcraft\HookFlow\Console\Commands\DiscoverHooksCommand;
use Skillcraft\HookFlow\Console\Commands\DocumentHooksCommand;
use Skillcraft\HookFlow\Console\Commands\ListHooksCommand;
use Skillcraft\HookFlow\Console\Commands\ValidateHooksCommand;
use Skillcraft\HookFlow\Support\HookExecutor;
use Skillcraft\HookFlow\Support\HookRegistry;

class HookFlowServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HookRegistry::class);
        $this->app->singleton(HookExecutor::class);

        // Register the hook registry as a facade accessible alias
        $this->app->alias(HookRegistry::class, 'hook');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DiscoverHooksCommand::class,
                DocumentHooksCommand::class,
                ListHooksCommand::class,
                ValidateHooksCommand::class,
            ]);
        }
    }
}
