# Headless Hook Examples

This directory contains example hook definitions to demonstrate how to use the Headless Hook package effectively in your Laravel application.

## Hook Types

### Action Hooks
Action hooks are used to perform operations at specific points in your application without modifying data. They are ideal for:
- Logging and monitoring
- Sending notifications
- Triggering external systems
- Performing validation
- Integration with third-party services

Example: [BeforeUserCreatedHook](UserHooks/BeforeUserCreatedHook.php)

```php
// Registering the hook
use Examples\UserHooks\BeforeUserCreatedHook;
use Skillcraft\HookFlow\Facades\Hook;

Hook::register(new BeforeUserCreatedHook());

// Using the hook in your code
public function store(Request $request)
{
    $userData = $request->validated();
    
    // Execute the hook before creating the user
    // The hook identifier is the fully qualified class name
    Hook::execute(BeforeUserCreatedHook::class, [
        'userData' => $userData,
        'context' => 'web'
    ]);
    
    // Create the user
    $user = User::create($userData);
}
```

### Filter Hooks
Filter hooks are used to modify data at specific points in your application. They are perfect for:
- Data sanitization and validation
- Data transformation and normalization
- Adding computed or default values
- Data enrichment from external sources
- Format standardization

Example: [FilterUserDataHook](UserHooks/FilterUserDataHook.php)

```php
// Registering the hook
use Examples\UserHooks\FilterUserDataHook;
use Skillcraft\HookFlow\Facades\Hook;

Hook::register(new FilterUserDataHook());

// Using the hook in your code
public function save(array $userData, bool $isNewUser = false): User
{
    // Apply the filter hook to modify the user data
    // The hook identifier is the fully qualified class name
    $filteredData = Hook::apply(FilterUserDataHook::class, $userData, [
        'isNewUser' => $isNewUser
    ]);
    
    // Save the filtered data
    return User::create($filteredData);
}
```

## Hook Components

### 1. Identifier
Each hook automatically uses its fully qualified class name as its unique identifier. You can override this if needed:
```php
public function getIdentifier(): string
{
    return 'custom-identifier'; // Optional: only if you need a custom identifier
}
```

### 2. Description
Provide a clear description of when and why the hook is triggered:
```php
public function getDescription(): string
{
    return 'Triggered before a new user is created. Use this hook to perform validation, modify user data, or integrate with external systems.';
}
```

### 3. Plugin
Group hooks by their source plugin:
```php
public function getPlugin(): string
{
    return 'user-management';
}
```

### 4. Parameters
Define the parameters that your hook accepts:
```php
public function getParameters(): array
{
    return [
        'userData' => 'array',
        'context' => 'string'
    ];
}
```

### 5. Trigger Point
Specify when the hook is triggered:
```php
public function getTriggerPoint(): string
{
    return 'UserController@store:before';
}
```

### 6. Priority
Control execution order (lower numbers run first):
```php
public function getPriority(): int
{
    return 5; // High priority
}
```

## Best Practices

1. **Choose Identifiers Wisely**
   - Use class names for better IDE support and refactorability
   - Use custom identifiers when you need more control or backward compatibility

2. **Document Your Hooks**
   - Add clear descriptions
   - Specify the plugin that owns the hook
   - Document all parameters with their types
   - Provide example usage in docblocks

3. **Validate Input**
   - Always validate hook parameters
   - Use type hints where possible
   - Throw descriptive exceptions for invalid input

4. **Follow Naming Conventions**
   - Action hooks: `BeforeUserCreatedHook`, `AfterOrderShippedHook`
   - Filter hooks: `FilterUserDataHook`, `TransformOrderItemsHook`

5. **Use Trigger Points**
   - Specify where in the codebase the hook is triggered
   - Follow format: `Class@method`

6. **Handle Priorities**
   - Use priorities to control execution order
   - Lower numbers run first (1-100)
   - Document priority requirements

## Managing Hooks

### Discovery
Find all hooks in your application:
```bash
php artisan hook:flows:discover
```

### Validation
Validate your hooks:
```bash
php artisan hook:flows:validate
```

### Listing
List all registered hooks:
```bash
php artisan hook:flows:list
```

### JSON Output
Get hooks information in JSON format:
```bash
php artisan hook:flows:discover --json
