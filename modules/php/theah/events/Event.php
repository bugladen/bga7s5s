<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
abstract class Event
{
    const CHANGE_ACTIVE_PLAYER_PRIORITY = 8;
    const TRANSITION_PRIORITY = 8;
    const REACTION_PRIORITY = 6;
    const LOWEST_PRIORITY = 5;
    const LOW_PRIORITY = 4;
    const MEDIUM_PRIORITY = 3;
    const HIGH_PRIORITY = 2;
    const HIGHEST_PRIORITY = 1;

    public Theah $theah;
    public int $priority;
    public bool $runEventHubAfterCards;
    public bool $canceled;
    public bool $wasStacked;

    public function __construct()
    {
        $this->priority = Event::MEDIUM_PRIORITY;
        $this->runEventHubAfterCards = false;
        $this->canceled = false;
        $this->wasStacked = false;
    }

    public function queueEvent(Event $event)
    {
        $this->theah->queueEvent($event);
    }

}