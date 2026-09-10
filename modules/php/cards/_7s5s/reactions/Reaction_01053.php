<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01053 extends RiskReaction implements ICancelReaction
{
    private int $SourceId = 0;
    private int $TargetId = 0;

    // WHY: Same effect set as Unyielding Loyalty (Reaction_01032). While the player
    // has chosen cancel and is in (or backing out of) pay, clone+cancel each effect
    // so EventHub (runEventHubAfterCards) never applies it. Decline re-queues them.
    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardEngarded $engardedEvent = null;
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;
    private ?EventCharacterBeingHealed $characterHealedEvent = null;
    private ?EventChallengeIssued $challengeIssuedEvent = null;
    private ?EventCharacterTargeted $characterTargetedEvent = null;

    private bool $holdingEffects = false;
    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Sorcerer Ability Targeting a Card");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel Sorcerer Ability Targeting Card: ');        
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $target = $theah->getCardById($this->TargetId);
        $performers = $theah->getCharactersAtLocationbyPlayerId($target->Location, $owner->ControllerId);

        foreach ($performers as $performer)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Wound and Cancel Sorcerer Ability with %s'), $performer->Name), "cancel-{$performer->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    private function holdEvent(Event $event, string $property): void
    {
        $owner = $this->getOwningCard($event->theah);

        if ($this->skipNextEvent)
        {
            $this->skipNextEvent = false;
            $owner->IsUpdated = true;
            return;
        }

        if ($this->$property !== null)
        {
            return;
        }

        $clone = clone $event;
        unset($clone->theah);
        $this->$property = $clone;
        $event->canceled = true;
        $owner->IsUpdated = true;

        if ($event->batchId)
        {
            $event->theah->deleteEventBatch($event->batchId);
        }
    }

    private function clearEvents(Game $game): void
    {
        $this->engagedEvent = null;
        $this->engardedEvent = null;
        $this->cardMovingEvent = null;
        $this->characterWoundedEvent = null;
        $this->characterHealedEvent = null;
        $this->characterTargetedEvent = null;

        if ($this->challengeIssuedEvent != null)
        {
            $game->globals->set(Game::CHALLENGE_CANCELLED, true);
        }
        $this->challengeIssuedEvent = null;
    }

    private function releaseEvents(Game $game): void
    {
        if ($this->engagedEvent)
        {
            $game->theah->queueEvent($this->engagedEvent);
            $this->engagedEvent = null;
        }

        if ($this->engardedEvent)
        {
            $game->theah->queueEvent($this->engardedEvent);
            $this->engardedEvent = null;
        }

        if ($this->cardMovingEvent)
        {
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
        }

        if ($this->characterWoundedEvent)
        {
            $game->theah->queueEvent($this->characterWoundedEvent);
            $this->characterWoundedEvent = null;
        }

        if ($this->characterHealedEvent)
        {
            $game->theah->queueEvent($this->characterHealedEvent);
            $this->characterHealedEvent = null;
        }

        if ($this->characterTargetedEvent)
        {
            $game->theah->queueEvent($this->characterTargetedEvent);
            $this->characterTargetedEvent = null;
        }

        if ($this->challengeIssuedEvent)
        {
            $game->theah->queueEvent($this->challengeIssuedEvent);
            $this->challengeIssuedEvent = null;
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSorcererAbilityStart && $this->isAvailable() && ! $this->holdingEffects)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $source = $event->theah->getCardById($event->sourceId);
                $ability = $source->getAbilityById($event->abilityId);
                if ($event->targetId != 0)
                {
                    $target = $event->theah->getCardById($event->targetId);
                
                    if (($ability instanceof IAbilityThatTargetsCards || $ability instanceof IAbilityThatTargetsCharacters) && $target->Location != Game::LOCATION_PLAYER_HOME)
                    {
                        $performers = $event->theah->getCharactersAtLocationbyPlayerId($target->Location, $owner->ControllerId);
                        if (count($performers) > 0)
                        {
                            $this->TargetId = $event->targetId;
                            $this->SourceId = $event->sourceId;
                            $owner->IsUpdated = true;
        
                            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                            $event->theah->stackEvent($transition);
                        }
                    }
                }
            }
        }

        if ($event instanceof EventCharacterTargeted && $this->isAvailable() && ! $this->holdingEffects && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $source = $event->theah->getCardById($event->sourceId);
                $ability = $source ? $source->getAbilityById($event->abilityId) : null;
                if ($event->targetId != 0 && $ability instanceof ISorcererAbility)
                {
                    //Hexenjagd text says "Sorcerer ability targets a card"; characters are cards, so character-targeting Sorceries trigger here. The existing EventSorcererAbilityStart branch only catches IAbilityThatTargetsCards (mutually exclusive with IAbilityThatTargetsCharacters per CLAUDE.md).
                    $target = $event->theah->getCardById($event->targetId);
                    if ($target && $target->Location != Game::LOCATION_PLAYER_HOME)
                    {
                        $performers = $event->theah->getCharactersAtLocationbyPlayerId($target->Location, $owner->ControllerId);
                        if (count($performers) > 0)
                        {
                            $this->TargetId = $event->targetId;
                            $this->SourceId = $event->sourceId;
                            $owner->IsUpdated = true;

                            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                            $event->theah->stackEvent($transition);
                        }
                    }
                }
            }
        }

        // WHY: Same effect types as Reaction_01032. All have runEventHubAfterCards,
        // so canceling here prevents EventHub from applying them. Clone kept for Decline.
        if ($this->holdingEffects && ! $event->canceled)
        {
            if ($event instanceof EventCardEngaged && $event->cardId == $this->TargetId)
            {
                $this->holdEvent($event, 'engagedEvent');
            }

            if ($event instanceof EventCardEngarded && $event->cardId == $this->TargetId)
            {
                $this->holdEvent($event, 'engardedEvent');
            }

            if ($event instanceof EventCardMoving && $event->cardId == $this->TargetId)
            {
                $this->holdEvent($event, 'cardMovingEvent');
            }

            if ($event instanceof EventCharacterBeingWounded && $event->characterId == $this->TargetId)
            {
                $this->holdEvent($event, 'characterWoundedEvent');
            }

            if ($event instanceof EventCharacterBeingHealed && $event->characterId == $this->TargetId)
            {
                $this->holdEvent($event, 'characterHealedEvent');
            }

            if ($event instanceof EventCharacterTargeted && $event->targetId == $this->TargetId)
            {
                $this->holdEvent($event, 'characterTargetedEvent');
            }

            if ($event instanceof EventChallengeIssued
                && ($event->defenderId == $this->TargetId || $event->challengerId == $this->TargetId))
            {
                $this->holdEvent($event, 'challengeIssuedEvent');
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId != 'decline')
            {
                $game = $event->theah->game;
                $performerId = str_replace("cancel-", "", $event->reactionId);
                $performer = $game->theah->getCharacterById($performerId);
    
                $target = $game->theah->getCardById($this->TargetId);
                $owner = $game->theah->getCardById($this->OwnerId);
    
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $game->theah->deleteEventsTargetingCard($this->TargetId);
                $game->theah->deleteTransitionEventsBySourceId($this->SourceId);
    
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to cancel the Sorcerer Ability Targeting ${card_inject_code}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getActivePlayerName(),
                    "card_inject_code" => $target->getInjectCode(),
                ]);
    
                $this->clearEvents($game);
                $this->holdingEffects = false;
                $this->setUsed($game->theah, true);
            }
        }        
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId != 'decline')
        {
            $this->holdingEffects = true;
            $this->skipNextEvent = false;
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        if ($reactionId == 'decline')
        {
            $this->releaseEvents($game);
            $this->holdingEffects = false;
            $this->skipNextEvent = true;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
