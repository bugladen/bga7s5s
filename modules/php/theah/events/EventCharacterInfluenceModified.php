<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterInfluenceModified extends Event
{
    public int $PlayerId;
    public int $CharacterId;
    public int $OldInfluence;
    public int $NewInfluence;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::MEDIUM_PRIORITY;

        $this->PlayerId = 0;
        $this->CharacterId = 0;
        $this->OldInfluence = 0;
        $this->NewInfluence = 0;
    }
}