# 🪝 Hook Flow

> A powerful and flexible hook system for Laravel applications 🚀

[![Latest Version on Packagist](https://img.shields.io/packagist/v/skillcraft/hook-flow.svg?style=flat-square)](https://packagist.org/packages/skillcraft/hook-flow)
[![Total Downloads](https://img.shields.io/packagist/dt/skillcraft/hook-flow.svg?style=flat-square)](https://packagist.org/packages/skillcraft/hook-flow)
[![Tests](https://img.shields.io/github/actions/workflow/status/skillcraft/hook-flow/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/skillcraft/hook-flow/actions/workflows/run-tests.yml)
[![Code Style](https://img.shields.io/github/actions/workflow/status/skillcraft/hook-flow/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/skillcraft/hook-flow/actions/workflows/fix-php-code-style-issues.yml)

## ✨ Features

- 🔄 **Seamless Flow**: Effortlessly extend your application with action and filter hooks
- 🛡️ **Type-Safe**: Full type hinting and validation for hook parameters
- 🔍 **Auto-Discovery**: Automatically find and register hooks in your application
- 🎯 **Clean API**: Intuitive and fluent API for registering and executing hooks
- ✅ **Validation**: Built-in validation for hook definitions and parameters
- 📊 **Priority System**: Control hook execution order with priorities
- 💻 **Artisan Commands**: Manage hooks via command line interface
- 📝 **JSON Output**: Export hook information in JSON format
- 🧠 **IDE Support**: Full IDE support with class-based hook identifiers

## 📦 Installation

You can install the package via composer:

```bash
composer require skillcraft/hook-flow
```

## 🚀 Quick Start

### 1. Define a Hook

By default, the hook identifier will be the fully qualified class name:

```php
use Skillcraft\HookFlow\HookDefinition;

class BeforeUserCreatedHook extends HookDefinition
{
    public function execute(array $args): void
    {
        // Your hook logic here
        logger()->info('User being created', $args);
    }
}
```

Or you can specify a custom identifier:

```php
use Skillcraft\HookFlow\HookDefinition;

class BeforeUserCreatedHook extends HookDefinition
{
    public function getIdentifier(): string
    {
        return 'before_user_created';
    }

    public function execute(array $args): void
    {
        // Your hook logic here
        logger()->info('User being created', $args);
    }
}
```

### 2. Register and Execute

Using class name as identifier:
```php
use Skillcraft\HookFlow\Facades\Hook;

// Register
Hook::register(new BeforeUserCreatedHook());

// Execute using class name
Hook::execute(BeforeUserCreatedHook::class, [
    'userData' => $request->validated(),
    'context' => 'web'
]);
```

Using custom identifier:
```php
// Register
Hook::register(new BeforeUserCreatedHook());

// Execute using custom identifier
Hook::execute('before_user_created', [
    'userData' => $request->validated(),
    'context' => 'web'
]);
```

## 🎯 Use Cases

### 🎬 Action Hooks
Perfect for:
- 📝 Logging and monitoring
- 📧 Sending notifications
- 🔌 Triggering external systems
- ✅ Performing validation
- 🔄 Third-party integrations

Example:
```php
class LogUserLoginHook extends HookDefinition
{
    public function execute(array $args): void
    {
        logger()->info('User logged in', [
            'user_id' => $args['user']->id,
            'ip' => $args['ip']
        ]);
    }
}

// Register and execute
Hook::register(new LogUserLoginHook());
Hook::execute(LogUserLoginHook::class, [
    'user' => $user,
    'ip' => $request->ip()
]);
```

### 🔄 Filter Hooks
Ideal for:
- 🧹 Data sanitization
- 🔄 Data transformation
- ➕ Adding computed values
- 🔍 Data enrichment
- 📋 Format standardization

Example:
```php
class EnrichUserDataHook extends HookDefinition
{
    public function isFilter(): bool
    {
        return true;
    }

    public function apply($value, array $args): mixed
    {
        return array_merge($value, [
            'full_name' => $value['first_name'] . ' ' . $value['last_name'],
            'age' => now()->diffInYears($value['birth_date'])
        ]);
    }
}

// Register and execute
Hook::register(new EnrichUserDataHook());
$enrichedData = Hook::apply(EnrichUserDataHook::class, $userData);
```

## 🛠️ Available Commands

```bash
# Validate hook definitions
php artisan hook:flows:validate

# List all registered hooks
php artisan hook:flows:list

# Generate hook documentation
php artisan hook:flows:document

# Get hooks info in JSON format
php artisan hook:flows:validate --json
```

## 📚 Documentation

Check out our [examples directory](examples) for detailed examples and best practices.

### 🎨 Hook Types

#### 1. Action Hooks
Execute operations without modifying data:
```php
// Using class name
Hook::execute(MyActionHook::class, ['param' => 'value']);
// Using custom identifier
Hook::execute('my_action_hook', ['param' => 'value']);
```

#### 2. Filter Hooks
Modify and return data:
```php
// Using class name
$filtered = Hook::apply(MyFilterHook::class, $data, ['context' => 'api']);
// Using custom identifier
$filtered = Hook::apply('my_filter_hook', $data, ['context' => 'api']);
```

### ⚡ Hook Priority

Control execution order with priorities:
```php
public function getPriority(): int
{
    return 5; // Higher priority (runs earlier)
}
```

### 📝 Hook Parameters

Define expected parameters for better IDE support and validation:
```php
public function getParameters(): array
{
    return [
        'user_id' => 'integer',
        'action' => 'string',
        'metadata' => 'array'
    ];
}
```

## 🧪 Testing

```bash
ddev exec composer test
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover any security related issues, please email skillcraft.opensource@pm.me instead of using the issue tracker.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 👏 Credits

- [William Troiano](https://williamtroiano.dev)
- [All Contributors](../../contributors)

---

Made with ❤️ by [William Troiano](https://williamtroiano.dev)
