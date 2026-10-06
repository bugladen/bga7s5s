<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

class FakeGamestate
{
    /** @var list<string|null> */
    public array $transitions = [];

    public function nextState(?string $transition = null): void
    {
        $this->transitions[] = $transition;
    }

    public function changeActivePlayer(int $playerId): void
    {
    }
}
