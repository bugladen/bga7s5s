<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

class FakeGamestate
{
    /** @var list<string|null> */
    public array $transitions = [];

    // WHY: _02046 Winter's Wind engages a muster only when the main state id's first two
    // digits are 30–69 (High Drama). Tests set this; 0 is outside that band.
    public ?int $currentMainStateId = 0;

    public function getCurrentMainStateId(): ?int
    {
        return $this->currentMainStateId;
    }

    public function nextState(?string $transition = null): void
    {
        $this->transitions[] = $transition;
    }

    /** @var list<int> */
    public array $activePlayerChanges = [];

    public function changeActivePlayer(int $playerId): void
    {
        $this->activePlayerChanges[] = $playerId;
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
