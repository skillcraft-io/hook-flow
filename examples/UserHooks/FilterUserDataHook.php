<?php

namespace Examples\UserHooks;

use Skillcraft\HookFlow\HookDefinition;
use Illuminate\Support\Collection;

/**
 * Hook that filters user data before it is saved.
 * This is an example of a filter hook that allows plugins to modify
 * user data before it is saved to the database.
 * 
 * This hook uses a custom identifier instead of the class name.
 *
 * @method void execute(array $args) Execute the action hook logic (not used for filter hooks)
 * @method bool isFilter() Whether this is a filter hook (returns true)
 * @method mixed apply($value, array $args) Apply the filter hook logic
 */
class FilterUserDataHook extends HookDefinition
{
    /**
     * Get the hook identifier.
     * This example uses a custom identifier instead of the class name.
     */
    public function getIdentifier(): string
    {
        return 'filter_user_data';
    }

    /**
     * Get the description of the hook.
     * This should clearly explain when and why this hook is triggered.
     */
    public function getDescription(): string
    {
        return 'Filters user data before it is saved to the database. Use this hook to modify, sanitize, or enrich user data.';
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
            'isNewUser' => 'bool',
        ];
    }

    /**
     * Get the trigger point of this hook.
     * This helps developers understand where in the codebase this hook is triggered.
     */
    public function getTriggerPoint(): string
    {
        return 'UserRepository@save';
    }

    /**
     * Specify that this is a filter hook.
     * Filter hooks modify and return data instead of just performing actions.
     */
    public function isFilter(): bool
    {
        return true;
    }

    /**
     * Apply the filter to the user data.
     * This is where you implement the actual filtering logic.
     *
     * Example usage:
     * ```php
     * use Examples\UserHooks\FilterUserDataHook;
     * 
     * // Register the hook
     * Hook::register(new FilterUserDataHook());
     * 
     * // Apply the filter using custom identifier
     * $userData = Hook::apply('filter_user_data', $userData, [
     *     'isNewUser' => true
     * ]);
     * ```
     *
     * @param array $value The user data to filter
     * @param array $args Additional arguments
     * @return array The filtered user data
     */
    public function apply($value, array $args): array
    {
        // Example: Sanitize and format user data
        $userData = $value;
        
        // Trim whitespace from string fields
        foreach (['name', 'email', 'username'] as $field) {
            if (isset($userData[$field])) {
                $userData[$field] = trim($userData[$field]);
            }
        }

        // Convert email to lowercase
        if (isset($userData['email'])) {
            $userData['email'] = strtolower($userData['email']);
        }

        // Add metadata for new users
        if ($args['isNewUser']) {
            $userData['created_at'] = now();
            $userData['status'] = 'pending';
        }

        return $userData;
    }

    /**
     * Validate the hook definition.
     * This is called when discovering hooks to ensure they are properly configured.
     *
     * @return \Illuminate\Support\Collection<string> Collection of validation error messages
     */
    public function validate(): Collection
    {
        $errors = collect();

        if (empty($this->getDescription())) {
            $errors->push('Hook description cannot be empty');
        }

        if (empty($this->getPlugin())) {
            $errors->push('Hook plugin cannot be empty');
        }

        if (empty($this->getParameters())) {
            $errors->push('Hook must accept at least one argument');
        }

        return $errors;
    }
}
