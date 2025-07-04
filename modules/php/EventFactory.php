<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\Theah\Events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\Theah\Events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChangeActivePlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterPutIntoApproachDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventClaimOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class EventFactory
{
    private static function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className();
        return $event;
    }

    public static function createActionTriggeredEvent(int $playerId, int $performerId, string $actionId): EventActionTriggered
    {
        $event = self::createEvent(Events::ActionTriggered);
        if ($event instanceof EventActionTriggered)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->actionId = $actionId;
        }

        return $event;
    }

    public static function createActionUsedEvent(int $playerId, int $ownerId, string $actionId, bool $used): EventActionUsed
    {
        $event = self::createEvent(Events::ActionUsed);
        if ($event instanceof EventActionUsed)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->actionId = $actionId;
            $event->used = $used;
        }

        return $event;
    }

    public static function createApproachCharacterPlayedEvent(int $playerId, int $characterId): EventApproachCharacterPlayed
    {
        $event = self::createEvent(Events::ApproachCharacterPlayed);
        if ($event instanceof EventApproachCharacterPlayed)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
        }

        return $event;
    }

    public static function createAttachmentEquippedEvent(int $playerId, int $characterId, int $attachmentId, int $discount, int $cost, bool $asAction = true): EventAttachmentEquipped
    {
        $event = self::createEvent(Events::AttachmentEquipped);
        if ($event instanceof EventAttachmentEquipped)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
            $event->attachmentId = $attachmentId;
            $event->discount = $discount;
            $event->cost = $cost;
            $event->asAction = $asAction;
        }

        return $event;
    }

    public static function createAttachmentUnequippedEvent(int $playerId, int $characterId, int $attachmentId): EventAttachmentUnequipped
    {
        $event = self::createEvent(Events::AttachmentUnequipped);
        if ($event instanceof EventAttachmentUnequipped)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
            $event->attachmentId = $attachmentId;
        }

        return $event;
    }

    public static function createCardAddedToCityDeckEvent(int $playerId, int $cardId, bool $onTop): EventCardAddedToCityDeck
    {
        $event = self::createEvent(Events::CardAddedToCityDeck);
        if ($event instanceof EventCardAddedToCityDeck)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->onTop = $onTop;
        }

        return $event;
    }

    public static function createCardAddedToFactionDeckEvent(int $playerId, int $cardId, bool $onTop): EventCardAddedToFactionDeck
    {
        $event = self::createEvent(Events::CardAddedToFactionDeck);
        if ($event instanceof EventCardAddedToFactionDeck)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->onTop = $onTop;
        }

        return $event;
    }

    public static function createCardAddedToCityDiscardPileEvent(int $playerId, int $cardId, string $location): EventCardAddedToCityDiscardPile
    {
        $event = self::createEvent(Events::CardAddedToCityDiscardPile);
        if ($event instanceof EventCardAddedToCityDiscardPile)
        {
            $event->cardId = $cardId;
            $event->fromLocation = $location;
            $event->playerId = $playerId;
        }
        return $event;
    }
    
    public static function createCardAddedToHandEvent(int $playerId, int $cardId): EventCardAddedToHand
    {
        $event = self::createEvent(Events::CardAddedToHand);
        if ($event instanceof EventCardAddedToHand)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
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

    public static function createCardDiscardedFromPlayEvent(int $ownerId, int $cardId, string $location): EventCardDiscardedFromPlay
    {
        $event = self::createEvent(Events::CardDiscardedFromPlay);
        if ($event instanceof EventCardDiscardedFromPlay)
        {
            $event->ownerId = $ownerId;
            $event->cardId = $cardId;
            $event->fromLocation = $location;
        }
        return $event;
    }

    public static function createCardDrawnEvent(int $playerId, Card $card, string $reason): EventCardDrawn
    {
        $event = self::createEvent(Events::CardDrawn);
        if ($event instanceof EventCardDrawn)
        {
            $event->playerId = $playerId;
            $event->card = $card;
            $event->reason = $reason;
        }
        return $event;
    }
    

    public static function createCardEngagedEvent(int $playerId, int $cardId, int $sourceId = 0): EventCardEngaged
    {
        $event = self::createEvent(Events::CardEngaged);
        if ($event instanceof EventCardEngaged)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->sourceId = $sourceId;
        }

        return $event;
    }

    public static function createCardEngardedEvent(int $playerId, int $cardId, int $sourceId = 0): EventCardEngarded
    {
        $event = self::createEvent(Events::CardEngarded);
        if ($event instanceof EventCardEngarded)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createCardMovedEvent(int $playerId, int $cardId, string $fromLocation, string $toLocation, bool $engage = true, int $sourceId = 0): EventCardMoved
    {
        $event = self::createEvent(Events::CardMoved);
        if ($event instanceof EventCardMoved)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->fromLocation = $fromLocation;
            $event->toLocation = $toLocation;
            $event->engage = $engage;
            $event->sourceId = $sourceId;
        }

        return $event;
    }

    public static function createCardRemovedFromCityDiscardPileEvent(int $playerId, int $cardId): EventCardRemovedFromCityDiscardPile
    {
        $event = self::createEvent(Events::CardRemovedFromCityDiscardPile);
        if ($event instanceof EventCardRemovedFromCityDiscardPile)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createCardRemovedFromPlayerDiscardPileEvent(int $playerId, int $cardId): EventCardRemovedFromPlayerDiscardPile
    {
        $event = self::createEvent(Events::CardRemovedFromPlayerDiscardPile);
        if ($event instanceof EventCardRemovedFromPlayerDiscardPile)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createCardRemovedFromPlayerFactionDeckEvent(int $playerId, int $cardId): EventCardRemovedFromPlayerFactionDeck
    {
        $event = self::createEvent(Events::CardRemovedFromPlayerFactionDeck);
        if ($event instanceof EventCardRemovedFromPlayerFactionDeck)
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

    public static function createCharacterInfluenceModifiedEvent(int $playerId, int $characterId, int $oldInfluence, int $newInfluence): EventCharacterInfluenceModified
    {
        $event = self::createEvent(Events::CharacterInfluenceModified);
        if ($event instanceof EventCharacterInfluenceModified)
        {
            $event->PlayerId = $playerId;
            $event->CharacterId = $characterId;
            $event->OldInfluence = $oldInfluence;
            $event->NewInfluence = $newInfluence;
        }
        return $event;
    }

    public static function createCharacterMusteredEvent(int $playerId, int $characterId, string $location): EventCharacterMustered
    {
        $event = self::createEvent(Events::CharacterMustered);
        if ($event instanceof EventCharacterMustered)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
            $event->location = $location;
        }

        return $event;
    }

    public static function createCharacterPutIntoApproachDeckEvent(int $playerId, int $characterId): EventCharacterPutIntoApproachDeck
    {
        $event = self::createEvent(Events::CharacterPutIntoApproachDeck);
        if ($event instanceof EventCharacterPutIntoApproachDeck)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
        }

        return $event;
    }

    public static function createCharacterWoundedEvent(int $characterId, int $sourceId, int $wounds, string $reason): EventCharacterWounded
    {
        $event = self::createEvent(Events::CharacterWounded);
        if ($event instanceof EventCharacterWounded)
        {
            $event->characterId = $characterId;
            $event->sourceId = $sourceId;
            $event->wounds = $wounds;
            $event->reason = $reason;
        }

        return $event;
    }

    public static function createCharacterHealedEvent(int $characterId, int $sourceId, int $wounds, string $reason): EventCharacterHealed
    {
        $event = self::createEvent(Events::CharacterHealed);
        if ($event instanceof EventCharacterHealed)
        {
            $event->characterId = $characterId;
            $event->sourceId = $sourceId;
            $event->wounds = $wounds;
            $event->reason = $reason;
        }

        return $event;
    }

    public static function createChallengeAcceptedEvent(int $challengerId, int $targetId): EventChallengeAccepted
    {
        $event = self::createEvent(Events::ChallengeAccepted);
        if ($event instanceof EventChallengeAccepted)
        {
            $event->challengerId = $challengerId;
            $event->targetId = $targetId;
        }

        return $event;
    }

public static function createChallengeRejectedEvent(int $challengerId, int $targetId): EventChallengeRejected
    {
        $event = self::createEvent(Events::ChallengeRejected);
        if ($event instanceof EventChallengeRejected)
        {
            $event->challengerId = $challengerId;
            $event->targetId = $targetId;
        }

        return $event;
    }
    

    public static function createCityCardAddedToLocationEvent(int $cardId, string $location): EventCityCardAddedToLocation
    {
        $event = self::createEvent(Events::CityCardAddedToLocation);
        if ($event instanceof EventCityCardAddedToLocation)
        {
            $event->cardId = $cardId;
            $event->location = $location;
        }

        return $event;
    }

    public static function createClaimOccuringEvent(int $playerId, int $performerId, string $location, Array $pressureTypes): EventClaimOccuring
    {
        $event = self::createEvent(Events::ClaimOccuring);
        if ($event instanceof EventClaimOccuring)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->location = $location;
            $event->pressureTypes = $pressureTypes;
        }
        return $event;
    }

    public static function createLocationPressuredEvent(int $playerId, int $performerId, string $location, string $pressureType, bool $success, string $totalsExplanation): EventLocationPressured
    {
        $event = self::createEvent(Events::LocationPressured);
        if ($event instanceof EventLocationPressured)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->location = $location;
            $event->pressureType = $pressureType;
            $event->success = $success;
            $event->totalsExplanation = $totalsExplanation;
        }

        return $event;
    }

    public static function createManeuverActivatedEvent(int $playerId, int $ownerId, string $maneuverId): EventManeuverActivated
    {
        $event = self::createEvent(Events::ManeuverActivated);
        if ($event instanceof EventManeuverActivated)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->maneuverId = $maneuverId;
        }

        return $event;
    }

    public static function createManeuverUsedEvent(int $playerId, int $ownerId, string $maneuverId, bool $used): EventManeuverUsed
    {
        $event = self::createEvent(Events::ManeuverUsed);
        if ($event instanceof EventManeuverUsed)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->maneuverId = $maneuverId;
            $event->used = $used;
        }

        return $event;
    }

    public static function createPlayerTurnEndEvent(int $playerId): EventPlayerTurnEnd
    {
        $event = self::createEvent(Events::PlayerTurnEnd);
        if ($event instanceof EventPlayerTurnEnd)
        {
            $event->playerId = $playerId;
        }

        return $event;
    }

    public static function createPlayerGainsReknownEvent(int $playerId, int $amount): EventPlayerGainsReknown
    {
        $event = self::createEvent(Events::PlayerGainsReknown);
        if ($event instanceof EventPlayerGainsReknown)
        {
            $event->playerId = $playerId;
            $event->amount = $amount;
        }

        return $event;
    }

    public static function createPlayerLosesReknownEvent(int $playerId, int $amount): EventPlayerLosesReknown
    {
        $event = self::createEvent(Events::PlayerLosesReknown);
        if ($event instanceof EventPlayerLosesReknown)
        {
            $event->playerId = $playerId;
            $event->amount = $amount;
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

    public static function createReactionUsedEvent(int $playerId, int $ownerId, string $reactionId, bool $used): EventReactionUsed
    {
        $event = self::createEvent(Events::ReactionUsed);
        if ($event instanceof EventReactionUsed)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->reactionId = $reactionId;
            $event->used = $used;
        }

        return $event;
    }

    public static function createReknownAddedToLocationEvent(int $playerId, string $location, int $amount, string $source)
    {
        $event = self::createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) 
        {
            $event->playerId = $playerId;
            $event->location = $location;
            $event->amount = $amount;
            $event->source = $source;
        }
        return $event;
    }

    public static function createReknownRemovedFromCardEvent(int $playerId, int $cardId, int $amount): EventReknownRemovedFromCard
    {
        $event = self::createEvent(Events::ReknownRemovedFromCard);
        if ($event instanceof EventReknownRemovedFromCard)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->amount = $amount;
        }

        return $event;
    }

    public static function createReknownRemovedFromLocationEvent(int $playerId, string $location, int $amount, string $source): EventReknownRemovedFromLocation
    {
        $event = self::createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation)
        {
            $event->playerId = $playerId;
            $event->location = $location;
            $event->amount = $amount;
            $event->source = $source;
        }

        return $event;
    }

    public static function createTechniqueActivatedEvent(int $playerId, int $ownerId, string $techniqueId): EventTechniqueActivated
    {
        $event = self::createEvent(Events::TechniqueActivated);
        if ($event instanceof EventTechniqueActivated)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->techniqueId = $techniqueId;
        }

        return $event;
    }

    public static function createTechniqueUsedEvent(int $playerId, int $ownerId, string $techniqueId, bool $used): EventTechniqueUsed
    {
        $event = self::createEvent(Events::TechniqueUsed);
        if ($event instanceof EventTechniqueUsed)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->techniqueId = $techniqueId;
            $event->used = $used;
        }

        return $event;
    }

    public static function createThreatModifiedEvent(int $challengerId, int $defenderId, int $challengerThreat, int $defenderThreat): EventThreatModified
    {
        $event = self::createEvent(Events::ThreatModified);
        if ($event instanceof EventThreatModified)
        {
            $event->challengerId = $challengerId;
            $event->defenderId = $defenderId;
            $event->challengerThreat = $challengerThreat;
            $event->defenderThreat = $defenderThreat;
        }
        return $event;
    }

    public static function createTransitionEvent(int $playerId, int $sourceId, string $transitionName, string $internalId = ""): EventTransition
    {
        $transition = self::createEvent(Events::Transition);
        if ($transition instanceof EventTransition)
        {
            $transition->playerId = $playerId;
            $transition->sourceId = $sourceId;
            $transition->transition = $transitionName;
            $transition->internalId = $internalId;
        }

        return $transition;
    }

}