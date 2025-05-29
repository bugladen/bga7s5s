<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventAttachmentEquipped extends Event
{
    public int $playerId;
    public int $performerId;
    public int $attachmentId;
    public int $discount;
    public int $cost;
    public bool $asAction;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;
        $this->performerId = 0;
        $this->attachmentId = 0;
        $this->discount = 0;
        $this->cost = 0;
        $this->asAction = true;
    }
}