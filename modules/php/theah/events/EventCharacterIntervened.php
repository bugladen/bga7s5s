<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventCharacterIntervened extends Event
{
    public int $playerId;
    public int $oldTargetId;
    public int $newTargetId;

    public function __construct()
    {
        parent::__construct();
    }
 
}