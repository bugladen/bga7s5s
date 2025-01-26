<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class EventTransition extends Event
{
    public string $transition;
    public $playerId;

    public function __construct()
    {
        parent::__construct();

        $this->transition = '';
        $this->playerId = null;
        $this->priority = Event::MEDIUM_PRIORITY;
    }

    public function getPlayerId(): ?int
    {
        return $this->playerId;
    }

}
