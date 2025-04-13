<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChangeActivePlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class EventFactory
{
    private static function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className();
        return $event;
    }

    public static function createAttachmentEquippedEvent(int $playerId, int $attachmentId, int $characterId, int $discount, int $cost): EventAttachmentEquipped
    {
        $event = self::createEvent(Events::AttachmentEquipped);
        if ($event instanceof EventAttachmentEquipped)
        {
            $event->playerId = $playerId;
            $event->attachmentId = $attachmentId;
            $event->performerId = $characterId;
            $event->discount = $discount;
            $event->cost = $cost;
        }

        return $event;
    }

    public static function createCardDiscardedFromHandEvent(int $playerId, int $cardId): EventCardDiscardedFromHand
    {
        $event = self::createEvent(Events::CardDiscardedFromHand);
        if ($event instanceof EventCardDiscardedFromHand)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createCardEngagedEvent(int $playerId, int $cardId): EventCardEngaged
    {
        $event = self::createEvent(Events::CardEngaged);
        if ($event instanceof EventCardEngaged)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createChangeActivePlayerEvent(int $playerId): EventChangeActivePlayer
    {
        $event = self::createEvent(Events::ChangeActivePlayer);
        if ($event instanceof EventChangeActivePlayer)
        {
            $event->playerId = $playerId;
        }

        return $event;
    }

    public static function createCharacterDestroyedEvent(int $playerId, int $characterId, string $reason): EventCharacterDestroyed
    {
        $event = self::createEvent(Events::CharacterDestroyed);
        if ($event instanceof EventCharacterDestroyed)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
            $event->reason = $reason;
        }

        return $event;
    }

    public static function createCharacterHealedEvent(int $characterId, int $attachmentId, int $wounds, string $reason): EventCharacterHealed
    {
        $event = self::createEvent(Events::CharacterHealed);
        if ($event instanceof EventCharacterHealed)
        {
            $event->characterId = $characterId;
            $event->sourceId = $attachmentId;
            $event->wounds = $wounds;
            $event->reason = $reason;
        }

        return $event;
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
        }

        return $transition;
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

}