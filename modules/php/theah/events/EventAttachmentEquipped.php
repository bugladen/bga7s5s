<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class EventAttachmentEquipped extends Event
{
    public int $playerId;
    public Attachment $attachment;
    public Character $performer;
    public int $discount;
    public int $cost;

    public function __construct()
    {
        parent::__construct();

        $this->priority = Event::MEDIUM_PRIORITY;
    }
}