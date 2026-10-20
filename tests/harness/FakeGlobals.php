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

    // WHY: StatesTrait (DEBUG_INCLUDE_CITY_CARD, FIRST_PLAYER) branches on existence, not value.
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function delete(string $key): void
    {
        unset($this->values[$key]);
    }

    public function all(): array
    {
        return $this->values;
    }
}
