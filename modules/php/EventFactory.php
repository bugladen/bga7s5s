<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class EventFactory
{
    private static function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className();
        return $event;
    }

    public static function createTransitionEvent(int $playerId, int $sourceId, string $internalId, string $transitionName): EventTransition
    {
        $transition = self::createEvent(Events::Transition);
        if ($transition instanceof EventTransition)
        {
            $transition->playerId = $playerId;
            $transition->sourceId = $sourceId;
            $transition->internalId = $internalId;
            $transition->transition = $transitionName;
        }

        return $transition;
    }
    public static function createReactionTransitionEvent(int $playerId, int $sourceId, string $internalId): EventTransition
    {
        $transition = self::createEvent(Events::Transition);
        if ($transition instanceof EventTransition)
        {
            $transition->playerId = $playerId;
            $transition->sourceId = $sourceId;
            $transition->internalId = $internalId;
            $transition->transition = 'reaction';
            $transition->priority = Event::LOWEST_PRIORITY;
        }

        return $transition;
    }
}