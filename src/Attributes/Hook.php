<?php

namespace Skillcraft\HookFlow\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Hook
{
    public function __construct(
        public readonly string $identifier,
        public readonly ?string $description = null,
        public readonly ?array $parameters = null,
        public readonly ?string $triggerPoint = null
    ) {
    }
}
