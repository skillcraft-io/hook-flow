<?php

namespace Skillcraft\HookFlow\Support;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Skillcraft\HookFlow\HookDefinition;

class HookExecutor
{
    /**
     * Execute a hook with the given arguments.
     *
     * @param HookDefinition $hook The hook to execute
     * @param array $args The arguments to pass to the hook
     * @return mixed The result of the hook execution
     * @throws InvalidArgumentException If the arguments don't match the hook's parameters
     */
    public function execute(HookDefinition $hook, array $args)
    {
        $this->validateArguments($hook, $args);

        if ($hook->isFilter()) {
            return $this->executeFilter($hook, $args);
        }

        return $this->executeAction($hook, $args);
    }

    /**
     * Execute a filter hook.
     *
     * @param HookDefinition $hook
     * @param array $args
     * @return mixed
     */
    protected function executeFilter(HookDefinition $hook, array $args)
    {
        // For filters, we expect the first argument to be the value being filtered
        $value = reset($args);
        
        // Execute the filter logic
        return $hook->apply($value, $args);
    }

    /**
     * Execute an action hook.
     *
     * @param HookDefinition $hook
     * @param array $args
     * @return void
     */
    protected function executeAction(HookDefinition $hook, array $args): void
    {
        // Execute the action logic
        $hook->execute($args);
    }

    /**
     * Validate that the provided arguments match the hook's parameters.
     *
     * @param HookDefinition $hook
     * @param array $args
     * @throws InvalidArgumentException
     */
    protected function validateArguments(HookDefinition $hook, array $args): void
    {
        $parameters = $hook->getParameters();
        $acceptedArgs = $hook->getAcceptedArgs();

        // Check if we have enough arguments
        if (count($args) < $acceptedArgs) {
            throw new InvalidArgumentException(
                sprintf(
                    'Hook %s requires %d arguments, %d provided',
                    $hook->getIdentifier(),
                    $acceptedArgs,
                    count($args)
                )
            );
        }

        // Validate argument types if type information is provided
        foreach ($parameters as $name => $type) {
            if (!isset($args[$name])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Missing required argument "%s" for hook %s',
                        $name,
                        $hook->getIdentifier()
                    )
                );
            }

            if (!$this->validateArgumentType($args[$name], $type)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid type for argument "%s" in hook %s. Expected %s, got %s',
                        $name,
                        $hook->getIdentifier(),
                        $type,
                        gettype($args[$name])
                    )
                );
            }
        }
    }

    /**
     * Validate the type of an argument.
     *
     * @param mixed $value
     * @param string $expectedType
     * @return bool
     */
    protected function validateArgumentType($value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'int', 'integer' => is_int($value),
            'bool', 'boolean' => is_bool($value),
            'float', 'double' => is_float($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'callable' => is_callable($value),
            'null' => is_null($value),
            default => true // For class types or mixed, we'll be lenient
        };
    }
}
