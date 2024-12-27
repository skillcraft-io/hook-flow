<?php
namespace Skillcraft\HookFlow\Tests\Doubles;
use Skillcraft\HookFlow\HookDefinition;
class DuplicateHook1 extends HookDefinition {
    public function getIdentifier(): string { return "duplicate-hook"; }
    public function getDescription(): string { return "desc"; }
    public function getPlugin(): string { return "plugin"; }
    public function getParameters(): array { 
        return [
            "param1" => "string"
        ]; 
    }
    public function getTriggerPoint(): string { return "trigger"; }
}