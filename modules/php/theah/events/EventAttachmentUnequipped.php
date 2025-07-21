<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventAttachmentUnequipped extends Event
{
    public int $playerId;
    public int $characterId;
    public int $attachmentId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->characterId = 0;
        $this->attachmentId = 0;
        
    }
}

