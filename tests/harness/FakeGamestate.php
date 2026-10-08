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

    /** @var list<array{playerId:int,transition:string}> */
    public array $nonMultiactive = [];

    // WHY: Action_01095b step 2 releases each discarding opponent via setPlayerNonMultiactive;
    // record the call so tests can assert who was released and on which transition.
    public function setPlayerNonMultiactive(int $playerId, string $nextState): bool
    {
        $this->nonMultiactive[] = ['playerId' => $playerId, 'transition' => $nextState];
        return true;
    }
}
