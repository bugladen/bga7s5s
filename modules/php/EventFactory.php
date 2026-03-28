<?php

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCalculatePayDiscount;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardHidden;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\Theah\Events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\Theah\Events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChangeActivePlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterPutIntoApproachDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterLostBrute;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromCard;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTableSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
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

    public static function createActionResolvedEvent(int $playerId): EventActionResolved
    {
        $event = self::createEvent(Events::ActionResolved);
        if ($event instanceof EventActionResolved)
        {
            $event->playerId = $playerId;
        }

        return $event;
    }

    public static function createActionTriggeredEvent(int $playerId, int $performerId, int $sourceId,string $actionId): EventActionTriggered
    {
        $event = self::createEvent(Events::ActionTriggered);
        if ($event instanceof EventActionTriggered)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->sourceId = $sourceId;
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

    public static function createAttachmentEquippedEvent(int $playerId, int $characterId, int $attachmentId, int $discount, int $cost, bool $asAction = true, string $explanations = '', bool $messageHidden = false): EventAttachmentEquipped
    {
        //getRequiredAttachTargetId() commit hook not required for Event Factory
        $event = self::createEvent(Events::AttachmentEquipped);
        if ($event instanceof EventAttachmentEquipped)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
            $event->attachmentId = $attachmentId;
            $event->discount = $discount;
            $event->cost = $cost;
            $event->asAction = $asAction;
            $event->explanations = $explanations;
            $event->messageHidden = $messageHidden;
        }

        return $event;
    }

    public static function createAttachmentMovedEvent(int $playerId, int $attachmentId, int $fromCharacterId, int $toCharacterId): EventAttachmentMoved
    {
        $event = self::createEvent(Events::AttachmentMoved);
        if ($event instanceof EventAttachmentMoved)
        {
            $event->playerId = $playerId;
            $event->attachmentId = $attachmentId;
            $event->fromCharacterId = $fromCharacterId;
            $event->toCharacterId = $toCharacterId;
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

    public static function createCalculatePayDiscountEvent(int $playerId, int $cardId, int $payStateType, string $internalId = ""): EventCalculatePayDiscount
    {
        $event = self::createEvent(Events::CalculatePayDiscount);
        if ($event instanceof EventCalculatePayDiscount)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->internalId = $internalId;
            $event->payStateType = $payStateType;
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

    public static function createCardAddedToCityDiscardPileEvent(int $playerId, int $cardId, string $location, int $sourceId = 0, bool $asEffect = false): EventCardAddedToCityDiscardPile
    {
        $event = self::createEvent(Events::CardAddedToCityDiscardPile);
        if ($event instanceof EventCardAddedToCityDiscardPile)
        {
            $event->cardId = $cardId;
            $event->fromLocation = $location;
            $event->playerId = $playerId;
            $event->sourceId = $sourceId;
            $event->asEffect = $asEffect;
        }
        return $event;
    }
    
    public static function createCardAddedToHandEvent(int $playerId, int $cardId, bool $hidden = false): EventCardAddedToHand
    {
        $event = self::createEvent(Events::CardAddedToHand);
        if ($event instanceof EventCardAddedToHand)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->hidden = $hidden;
        }
        return $event;
    }

    public static function createCardDiscardedFromHandEvent(int $ownerId, int $cardId, int $sourceId, $asPayment = false, bool $asPlayed = false, bool $asEffect = false): EventCardDiscardedFromHand
    {
        $event = self::createEvent(Events::CardDiscardedFromHand);
        if ($event instanceof EventCardDiscardedFromHand)
        {
            $event->ownerId = $ownerId;
            $event->cardId = $cardId;
            $event->sourceId = $sourceId;
            $event->AsPayment = $asPayment;
            $event->AsPlayed = $asPlayed;
            $event->asEffect = $asEffect;
        }

        return $event;
    }

    public static function createCardDiscardedFromPlayEvent(int $ownerId, int $cardId, string $location, int $sourceId = 0, bool $asEffect = false): EventCardDiscardedFromPlay
    {
        $event = self::createEvent(Events::CardDiscardedFromPlay);
        if ($event instanceof EventCardDiscardedFromPlay)
        {
            $event->ownerId = $ownerId;
            $event->cardId = $cardId;
            $event->fromLocation = $location;
            $event->sourceId = $sourceId;
            $event->asEffect = $asEffect;
        }
        return $event;
    }

    public static function createCardDrawnEvent(int $playerId, string $reason): EventCardDrawn
    {
        $event = self::createEvent(Events::CardDrawn);
        if ($event instanceof EventCardDrawn)
        {
            $event->playerId = $playerId;
            $event->reason = $reason;
        }
        return $event;
    }
    

    public static function createCardEngagedEvent(int $playerId, int $cardId, int $sourceId = 0, string $abilityId = ""): EventCardEngaged
    {
        $event = self::createEvent(Events::CardEngaged);
        if ($event instanceof EventCardEngaged)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->sourceId = $sourceId;
            $event->abilityId = $abilityId;
        }

        return $event;
    }

    public static function createCardEngardedEvent(int $playerId, int $cardId, int $sourceId = 0, string $abilityId = ""): EventCardEngarded
    {
        $event = self::createEvent(Events::CardEngarded);
        if ($event instanceof EventCardEngarded)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->sourceId = $sourceId;
            $event->abilityId = $abilityId;
        }

        return $event;
    }

    public static function createCardHiddenEvent(int $playerId, int $cardId): EventCardHidden
    {
        $event = self::createEvent(Events::CardHidden);
        if ($event instanceof EventCardHidden)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createCardMovingEvent(int $initiatingPlayerId, int $cardId, string $fromLocation, string $toLocation, bool $engage = true, int $sourceId = 0, string $abilityId = ""): EventCardMoving
    {
        $event = self::createEvent(Events::CardMoving);
        if ($event instanceof EventCardMoving)
        {
            $event->initiatingPlayerId = $initiatingPlayerId;
            $event->cardId = $cardId;
            $event->fromLocation = $fromLocation;
            $event->toLocation = $toLocation;
            $event->engage = $engage;
            $event->sourceId = $sourceId;
            $event->abilityId = $abilityId;
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

    public static function createCardRemovedFromLockerEvent(int $playerId, int $cardId): EventCardRemovedFromLocker
    {
        $event = self::createEvent(Events::CardRemovedFromLocker);
        if ($event instanceof EventCardRemovedFromLocker)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createCardRemovedFromPlayEvent(int $playerId, int $cardId, string $toLocation, bool $hidden = false): EventCardRemovedFromPlay
    {
        $event = self::createEvent(Events::CardRemovedFromPlay);
        if ($event instanceof EventCardRemovedFromPlay)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->toLocation = $toLocation;
            $event->hidden = $hidden;
        }

        return $event;
    }

    public static function createCardRemovedFromPlayerDiscardPileEvent(int $playerId, int $cardId, bool $messageHidden = false, bool $permanentlyHide = false): EventCardRemovedFromPlayerDiscardPile
    {
        $event = self::createEvent(Events::CardRemovedFromPlayerDiscardPile);
        if ($event instanceof EventCardRemovedFromPlayerDiscardPile)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->messageHidden = $messageHidden;
            $event->permanentlyHide = $permanentlyHide;
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

    public static function createCardSentToLockerEvent(int $playerId, int $cardId): EventCardSentToLocker
    {
        $event = self::createEvent(Events::CardSentToLocker);
        if ($event instanceof EventCardSentToLocker)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createChallengerSwappedEvent(int $playerId, int $oldChallengerId, int $newChallengerId): EventChallengerSwapped
    {
        $event = self::createEvent(Events::ChallengerSwapped);
        if ($event instanceof EventChallengerSwapped)
        {
            $event->playerId = $playerId;
            $event->oldChallengerId = $oldChallengerId;
            $event->newChallengerId = $newChallengerId;
        }

        return $event;
    }

    public static function createCharacterLostBruteEvent(int $playerId, int $characterId): EventCharacterLostBrute
    {
        $event = self::createEvent(Events::CharacterLostBrute);
        if ($event instanceof EventCharacterLostBrute)
        {
            $event->playerId = $playerId;
            $event->characterId = $characterId;
        }

        return $event;
    }

    public static function createDefenderSwappedEvent(int $playerId, int $oldDefenderId, int $newDefenderId): EventDefenderSwapped
    {
        $event = self::createEvent(Events::DefenderSwapped);
        if ($event instanceof EventDefenderSwapped)
        {
            $event->playerId = $playerId;
            $event->oldDefenderId = $oldDefenderId;
            $event->newDefenderId = $newDefenderId;
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

    public static function createCharacterCombatModifiedEvent(int $playerId, int $characterId, int $oldCombat, int $newCombat, string $reason = ''): EventCharacterCombatModified
    {
        $event = self::createEvent(Events::CharacterCombatModified);
        if ($event instanceof EventCharacterCombatModified)
        {
            $event->PlayerId = $playerId;
            $event->CharacterId = $characterId;
            $event->OldCombat = $oldCombat;
            $event->NewCombat = $newCombat;
            $event->Reason = $reason;
        }

        return $event;
    }

    public static function createCharacterFinesseModifedEvent(int $playerId, int $characterId, int $oldFinesse, int $newFinesse, string $reason = ''): EventCharacterFinesseModifed
    {
        $event = self::createEvent(Events::CharacterFinesseModifed);
        if ($event instanceof EventCharacterFinesseModifed)
        {
            $event->PlayerId = $playerId;
            $event->CharacterId = $characterId;
            $event->OldFinesse = $oldFinesse;
            $event->NewFinesse = $newFinesse;
            $event->Reason = $reason;
        }

        return $event;
    }

    public static function createCharacterInfluenceModifiedEvent(int $playerId, int $characterId, int $oldInfluence, int $newInfluence, string $reason = ''): EventCharacterInfluenceModified
    {
        $event = self::createEvent(Events::CharacterInfluenceModified);
        if ($event instanceof EventCharacterInfluenceModified)
        {
            $event->PlayerId = $playerId;
            $event->CharacterId = $characterId;
            $event->OldInfluence = $oldInfluence;
            $event->NewInfluence = $newInfluence;
            $event->Reason = $reason;
        }
        return $event;
    }

    public static function createCardMusteredEvent(int $playerId, int $cardId, string $location): EventCardMustered
    {
        $event = self::createEvent(Events::CardMustered);
        if ($event instanceof EventCardMustered)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->location = $location;
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

    public static function createCharacterBeingWoundedEvent(int $characterId, int $sourceId, int $wounds, string $reason, string $abilityId = ''): EventCharacterBeingWounded
    {
        $event = self::createEvent(Events::CharacterBeingWounded);
        if ($event instanceof EventCharacterBeingWounded)
        {
            $event->characterId = $characterId;
            $event->sourceId = $sourceId;
            $event->wounds = $wounds;
            $event->reason = $reason;
            $event->abilityId = $abilityId;
        }

        return $event;
    }

    public static function createCharacterBeingHealedEvent(int $characterId, int $sourceId, int $wounds, string $reason, string $abilityId = ''): EventCharacterBeingHealed
    {
        $event = self::createEvent(Events::CharacterBeingHealed);
        if ($event instanceof EventCharacterBeingHealed)
        {
            $event->characterId = $characterId;
            $event->sourceId = $sourceId;
            $event->wounds = $wounds;
            $event->reason = $reason;
            $event->abilityId = $abilityId;
        }

        return $event;
    }

    public static function createCharacterIntervenedEvent(int $playerId, int $oldTargetId, int $newTargetId): EventCharacterIntervened
    {
        $event = self::createEvent(Events::CharacterIntervened);
        if ($event instanceof EventCharacterIntervened)
        {
            $event->playerId = $playerId;
            $event->oldTargetId = $oldTargetId;
            $event->newTargetId = $newTargetId;
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

    public static function createCombatCardAnnouncedEvent(int $playerId, int $cardId): EventCombatCardAnnounced
    {
        $event = self::createEvent(Events::CombatCardAnnounced);
        if ($event instanceof EventCombatCardAnnounced)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
        }

        return $event;
    }

    public static function createDuelCalculateManeuverValuesEvent(int $actorId, int $adversaryId, string $maneuverId): EventDuelCalculateManeuverValues
    {
        $event = self::createEvent(Events::DuelCalculateManeuverValues);
        if ($event instanceof EventDuelCalculateManeuverValues)
        {
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->maneuverId = $maneuverId;
        }

        return $event;
    }

    public static function createDuelCalculateTechniqueValuesEvent(int $actorId, int $adversaryId, string $techniqueId): EventDuelCalculateTechniqueValues
    {
        $event = self::createEvent(Events::DuelCalculateTechniqueValues);
        if ($event instanceof EventDuelCalculateTechniqueValues)
        {
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->techniqueId = $techniqueId;            
        }

        return $event;
    }

    public static function createDuelEndOfRoundEvent(int $playerId, int $actorId): EventDuelEndOfRound
    {
        $event = self::createEvent(Events::DuelEndOfRound);
        if ($event instanceof EventDuelEndOfRound)
        {
            $event->playerId = $playerId;
            $event->actorId = $actorId;
        }
        return $event;
    }

    public static function createEnteringPayStateEvent(int $playerId, int $cardId, int $payStateType, string $internalId = ''): EventEnteringPayState
    {
        $event = self::createEvent(Events::EnteringPayState);
        if ($event instanceof EventEnteringPayState)
        {
            $event->playerId = $playerId;
            $event->cardId = $cardId;
            $event->payStateType = $payStateType;
            $event->internalId = $internalId;
        }
        
        return $event;
    }

    public static function createLocationBecomesUncontrolledEvent(int $playerId, string $location): EventLocationBecomesUncontrolled
    {
        $event = self::createEvent(Events::LocationBecomesUncontrolled);
        if ($event instanceof EventLocationBecomesUncontrolled)
        {
            $event->playerId = $playerId;
            $event->location = $location;
        }

        return $event;
    }

    public static function createLocationClaimedEvent(int $playerId, ?int $performerId, string $location): EventLocationClaimed
    {
        $event = self::createEvent(Events::LocationClaimed);
        if ($event instanceof EventLocationClaimed)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->location = $location;
        }

        return $event;
    }

    public static function createLocationPressuredEvent(int $playerId, ?int $performerId, string $location, string $pressureType, bool $success, string $totalsExplanation, int $difference): EventLocationPressured
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
            $event->difference = $difference;
        }

        return $event;
    }

    public static function createLocationPressureResultEvent(int $playerId, ?int $performerId, string $location, string $pressureType, bool $success, string $totalsExplanation, bool $highDramaBasicAction, string $abilityId): EventLocationPressureResult
    {
        $event = self::createEvent(Events::LocationPressureResult);
        if ($event instanceof EventLocationPressureResult)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->location = $location;
            $event->pressureType = $pressureType;
            $event->success = $success;
            $event->totalsExplanation = $totalsExplanation;
            $event->highDramaBasicAction = $highDramaBasicAction;
            $event->abilityId = $abilityId;
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

    public static function createManeuverCanceledEvent(int $playerId, string $maneuverId): EventManeuverCanceled
    {
        $event = self::createEvent(Events::ManeuverCanceled);
        if ($event instanceof EventManeuverCanceled)
        {
            $event->playerId = $playerId;
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

    public static function createPressureOccuringEvent(int $playerId, int $performerId, string $location, Array $pressureTypes): EventPressureOccuring
    {
        $event = self::createEvent(Events::PressureOccuring);
        if ($event instanceof EventPressureOccuring)
        {
            $event->playerId = $playerId;
            $event->performerId = $performerId;
            $event->location = $location;
            $event->pressureTypes = $pressureTypes;
        }
        return $event;
    }

    public static function createRangedAbilityPlayedEvent(int $playerId, int $sourceId, string $abilityId, int $performerId = 0, int $targetId = 0, string $targetLocation = ""): EventRangedAbilityPlayed
    {
        $event = self::createEvent(Events::RangedAbilityPlayed);
        if ($event instanceof EventRangedAbilityPlayed)
        {
            $event->playerId = $playerId;
            $event->sourceId = $sourceId;
            $event->abilityId = $abilityId;
            $event->performerId = $performerId;
            $event->targetId = $targetId;
            $event->targetLocation = $targetLocation;
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
            $transition->priority = Event::REACTION_PRIORITY;
        }

        return $transition;
    }

    public static function createReactionPayTransitionEvent(int $playerId, int $sourceId, string $internalId): EventTransition
    {
        $transition = self::createEvent(Events::Transition);
        if ($transition instanceof EventTransition)
        {
            $transition->playerId = $playerId;
            $transition->sourceId = $sourceId;
            $transition->internalId = $internalId;
            $transition->transition = 'pay';
            $transition->priority = Event::REACTION_PRIORITY;
        }

        return $transition;
    }

    public static function createRiskReactionTriggeredEvent(int $playerId, int $sourceId, string $internalId, string $reactionId): EventRiskReactionTriggered
    {
        $event = self::createEvent(Events::RiskReactionTriggered);
        if ($event instanceof EventRiskReactionTriggered)
        {
            $event->playerId = $playerId;
            $event->sourceId = $sourceId;
            $event->internalId = $internalId;
            $event->reactionId = $reactionId;
        }
        return $event;
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

    public static function createReknownAddedToLocationEvent(int $playerId, string $location, int $amount, string $description, bool $isMove = false)
    {
        $event = self::createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) 
        {
            $event->playerId = $playerId;
            $event->location = $location;
            $event->amount = $amount;
            $event->description = $description;
            $event->isMove = $isMove;
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

    public static function createResolveManeuverEvent(int $playerId, int $adversaryId, string $maneuverId): EventResolveManeuver
    {
        $event = self::createEvent(Events::ResolveManeuver);
        if ($event instanceof EventResolveManeuver)
        {
            $event->playerId = $playerId;
            $event->adversaryId = $adversaryId;
            $event->maneuverId = $maneuverId;
        }

        return $event;
    }

    public static function createResolveTechniqueEvent(int $playerId, int $actorId, int $adversaryId, string $techniqueId): EventResolveTechnique
    {
        $event = self::createEvent(Events::ResolveTechnique);
        if ($event instanceof EventResolveTechnique)
        {
            $event->playerId = $playerId;
            $event->actorId = $actorId;
            $event->adversaryId = $adversaryId;
            $event->techniqueId = $techniqueId;
        }

        return $event;
    }

    public static function createRiskPlayedEvent(int $playerId, int $riskId): EventRiskPlayed
    {
        $event = self::createEvent(Events::RiskPlayed);
        if ($event instanceof EventRiskPlayed)
        {
            $event->playerId = $playerId;
            $event->riskId = $riskId;
        }

        return $event;
    }

    public static function createSorcererAbilityStartEvent(int $playerId, int $sourceId, string $abilityId, int $performerId = 0, int $targetId = 0, string $targetLocation = ""): EventSorcererAbilityStart
    {
        $event = self::createEvent(Events::SorcererAbilityStart);
        if ($event instanceof EventSorcererAbilityStart)
        {
            $event->playerId = $playerId;
            $event->sourceId = $sourceId;
            $event->abilityId = $abilityId;
            $event->performerId = $performerId;
            $event->targetId = $targetId;
            $event->targetLocation = $targetLocation;
        }

        return $event;
    }

    public static function createSorcererAbilityPlayedEvent(int $playerId, int $sourceId, string $abilityId, int $performerId = 0, int $targetId = 0, string $targetLocation = ""): EventSorcererAbilityPlayed
    {
        $event = self::createEvent(Events::SorcererAbilityPlayed);
        if ($event instanceof EventSorcererAbilityPlayed)
        {
            $event->playerId = $playerId;
            $event->sourceId = $sourceId;
            $event->abilityId = $abilityId;
            $event->performerId = $performerId;
            $event->targetId = $targetId;
            $event->targetLocation = $targetLocation;
        }

        return $event;
    }

    public static function createTableSetupEvent(): EventTableSetup
    {
        $event = self::createEvent(Events::TableSetup);
        if ($event instanceof EventTableSetup)
        {
        }

        return $event;
    }

    public static function createTechniqueActivatedEvent(int $playerId, int $ownerId, string $techniqueId, bool $copied = false): EventTechniqueActivated
    {
        $event = self::createEvent(Events::TechniqueActivated);
        if ($event instanceof EventTechniqueActivated)
        {
            $event->playerId = $playerId;
            $event->ownerId = $ownerId;
            $event->techniqueId = $techniqueId;
            $event->copied = $copied;
        }

        return $event;
    }

    public static function createTechniqueCanceledEvent(int $playerId, string $techniqueId): EventTechniqueCanceled
    {
        $event = self::createEvent(Events::TechniqueCanceled);
        if ($event instanceof EventTechniqueCanceled)
        {
            $event->playerId = $playerId;
            $event->techniqueId = $techniqueId;
        }

        return $event;
    }

    public static function createTechniqueTransitionEvent(int $playerId, int $sourceId, string $transitionName, string $internalId): EventTransition
    {
        $transition = self::createEvent(Events::Transition);
        if ($transition instanceof EventTransition)
        {
            $transition->playerId = $playerId;
            $transition->sourceId = $sourceId;
            $transition->internalId = $internalId;
            $transition->transition = $transitionName;
            //This transition event is to present a choice to the player, so it should be the highest priority before any other events are processed
            $transition->priority = Event::HIGHEST_PRIORITY;
        }

        return $transition;
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

    public static function createThreatModifiedEvent(int $challengerThreat, int $defenderThreat, ?bool $challengerThreatIsLethal = null, ?bool $defenderThreatIsLethal = null): EventThreatModified
    {
        $event = self::createEvent(Events::ThreatModified);
        if ($event instanceof EventThreatModified)
        {
            $event->challengerThreat = $challengerThreat;
            $event->defenderThreat = $defenderThreat;
            $event->challengerThreatIsLethal = $challengerThreatIsLethal;
            $event->defenderThreatIsLethal = $defenderThreatIsLethal;
        }
        return $event;
    }

    public static function createGainLethalEvent(int $actorId, Theah $theah): EventThreatModified
    {
        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();
        $challengerThreatIsLethal = $actorId == $challengerId ? null : true;
        $defenderThreatIsLethal = $actorId == $defenderId ? null : true;

        return self::createThreatModifiedEvent(0, 0, $challengerThreatIsLethal, $defenderThreatIsLethal);
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