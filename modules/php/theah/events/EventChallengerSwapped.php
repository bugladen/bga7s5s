<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventChallengerSwapped extends Event
{
    public int $playerId;
    public int $oldChallengerId;
    public int $newChallengerId;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->playerId = 0;
        $this->oldChallengerId = 0;
        $this->newChallengerId = 0;
    }
}