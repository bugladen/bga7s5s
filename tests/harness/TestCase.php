<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

abstract class TestCase
{
    abstract public function name(): string;

    /** @return list<callable():void> keyed or list of [label, callable] */
    abstract public function tests(): array;

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }
}
