<?php

namespace Examples\UserHooks;

use Skillcraft\HookFlow\HookDefinition;
use Illuminate\Support\Collection;

/**
 * Hook that is triggered before a user is created.
 * This is an example of an action hook that allows plugins to perform
 * operations before a user is created in the system.
 * 
 * By default, the hook identifier will be the fully qualified class name:
 * Examples\UserHooks\BeforeUserCreatedHook
 *
 * @method void execute(array $args) Execute the action hook logic
 * @method bool isFilter() Whether this is a filter hook (default: false)
 * @method mixed apply($value, array $args) Apply the filter hook logic (not used for action hooks)
 */
class BeforeUserCreatedHook extends HookDefinition
{
    /**
     * Get the description of the hook.
     * This should clearly explain when and why this hook is triggered.
     */
    public function getDescription(): string
    {
        return 'Triggered before a new user is created in the system. Use this hook to perform validation, modify user data, or integrate with external systems.';
    }

    /**
     * Get the plugin that owns this hook.
     * This helps organize hooks by their source plugin.
     */
    public function getPlugin(): string
    {
        return 'user-management';
    }

    /**
     * Get the parameters that are passed to this hook.
     * Each parameter should have a name and a type.
     *
     * @return array<string, string> Map of parameter names to their types
     */
    public function getParameters(): array
    {
        return [
            'userData' => 'array',
            'context' => 'string',
        ];
    }

    /**
     * Get the trigger point of this hook.
     * This helps developers understand where in the codebase this hook is triggered.
     */
    public function getTriggerPoint(): string
    {
        return 'UserController@store';
    }

    /**
     * Validate the hook's configuration.
     * This is called when the hook is registered to ensure it is properly configured.
     */
    public function validate(): Collection
    {
        return parent::validate();
    }

    /**
     * Execute the hook's logic.
     * This is where you implement the actual functionality of the hook.
     *
     * Example usage:
     * ```php
     * use Examples\UserHooks\BeforeUserCreatedHook;
     * 
     * // Register the hook
     * Hook::register(new BeforeUserCreatedHook());
     * 
     * // Execute the hook using class name as identifier
     * Hook::execute(BeforeUserCreatedHook::class, [
     *     'userData' => $request->validated(),
     *     'context' => 'web'
     * ]);
     * ```
     */
    public function execute(array $args): void
    {
        // Example: Log the user creation attempt
        logger()->info('User creation initiated', [
            'data' => $args['userData'],
            'context' => $args['context']
        ]);

        // Example: Integrate with external system
        // event(new ExternalUserCreationEvent($args['userData']));

        // Example: Perform additional validation
        // if (!$this->validateUserData($args['userData'])) {
        //     throw new UserValidationException('Invalid user data');
        // }
    }
}
