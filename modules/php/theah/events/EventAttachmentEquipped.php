<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventAttachmentEquipped extends Event
{
    public int $playerId;
    public int $attachmentId;
    public int $performerId;
    public int $discount;
    public int $cost;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;
    }
}