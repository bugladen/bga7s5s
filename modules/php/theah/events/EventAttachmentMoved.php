<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventAttachmentMoved extends Event
{
    public int $playerId;
    public int $attachmentId;
    public int $fromCharacterId;
    public int $toCharacterId;

    public function __construct()
    {
        parent::__construct();

        $this->playerId = 0;
        $this->attachmentId = 0;
        $this->fromCharacterId = 0;
        $this->toCharacterId = 0;
    }
}