<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

class FakeGlobals
{
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function all(): array
    {
        return $this->values;
    }
}
