<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01032 extends RiskReaction
{
    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardEngarded $engardedEvent = null;
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;
    private ?EventCharacterBeingHealed $characterHealedEvent = null;
    private ?EventChallengeIssued $challengeIssuedEvent = null;
    private ?EventCharacterTargeted $characterTargetedEvent = null;

    private bool $inPlayRedHand = false;
    private bool $inHandThug = false;
    private bool $skipNextEvent = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Targeted Ability on one of your Characters");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $message = '';
        if ($this->inHandThug)
        {
            $message = $theah->game->translate('${you} may choose a Thug in your Hand to discard: ');
        }
        if ($this->inPlayRedHand)
        {
            $message = $theah->game->translate('${you} may choose an In-Play Red Hand to destroy: ');
        }

        return parent::getReactionDescription($theah) . $message;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);

        if ($this->inPlayRedHand)
        {
            $cards = $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
            $cards = array_filter($cards, fn($card) => $card->hasTrait("Red Hand"));
            foreach ($cards as $card)
            {
                $array[] = $this->createButtonProperty($theah->game, $card->Name, "destroy-$card->Id");
            }
        }

        if ($this->inHandThug)
        {
            $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            $cards = array_filter($cards, fn($card) => $card->hasTrait("Thug"));
            foreach ($cards as $card)
            {
                $array[] = $this->createButtonProperty($theah->game, $card->Name, "discard-$card->Id");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId): bool{
        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            $ability = $source->getAbilityById($abilityId);
            if ($ability instanceof IAbilityThatTargetsCharacters || $ability instanceof IAbilityThatTargetsCards)
            {
                return true;
            }
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action instanceof IAbilityThatTargetsCharacters || $action instanceof IAbilityThatTargetsCards)
        {
            return true;
        }

        return false;
    }
    
    private function thugsInHand(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        $hand = array_filter($hand, fn($card) => $card->hasTrait("Thug"));
        return count($hand) > 0;
    }

    private function redHandsInPlay(Theah $theah): bool
    {
        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Red Hand"));
        return count($characters) > 0;
    }    

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngaged && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $card = $event->theah->getCardById($event->cardId);
                if ($owner->ControllerId == $card->ControllerId && 
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->engagedEvent = clone $event;
                        unset($this->engagedEvent->theah);
                        $this->inPlayRedHand = true;                    
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->engagedEvent = clone $event;
                        unset($this->engagedEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;                        

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventCardEngarded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->cardId);
                if ($owner->ControllerId == $character->ControllerId && 
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->engardedEvent = clone $event;
                        unset($this->engardedEvent->theah);
                        $this->inPlayRedHand = true;                    
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->engardedEvent = clone $event;
                        unset($this->engardedEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;                        

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventCardMoving && $this->isAvailable() && !$event->canceled)
        {   
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $card = $event->theah->getCardById($event->cardId);
                if ($owner->ControllerId == $card->ControllerId && 
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->cardMovingEvent = clone $event;
                        unset($this->cardMovingEvent->theah);
                        $this->inPlayRedHand = true;                    
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->cardMovingEvent = clone $event;
                        unset($this->cardMovingEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;                        

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventCharacterBeingWounded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->characterId);
                if ($owner->ControllerId == $character->ControllerId && 
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->characterWoundedEvent = clone $event;
                        unset($this->characterWoundedEvent->theah);
                        $this->inPlayRedHand = true;                    
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->characterWoundedEvent = clone $event;
                        unset($this->characterWoundedEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;                        

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventCharacterBeingHealed && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->characterId);
                if ($owner->ControllerId == $character->ControllerId && 
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->characterHealedEvent = clone $event;
                        unset($this->characterHealedEvent->theah);
                        $this->inPlayRedHand = true;                    
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->characterHealedEvent = clone $event;
                        unset($this->characterHealedEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;                        

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventCharacterTargeted && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $character = $event->theah->getCharacterById($event->targetId);
                if ($owner->ControllerId == $character->ControllerId &&
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->characterTargetedEvent = clone $event;
                        unset($this->characterTargetedEvent->theah);
                        $this->inPlayRedHand = true;
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->characterTargetedEvent = clone $event;
                        unset($this->characterTargetedEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $defender = $event->theah->getCharacterById($event->defenderId);
                $challenger = $event->theah->getCharacterById($event->challengerId);
                if (($owner->ControllerId == $defender->ControllerId || $owner->ControllerId == $challenger->ControllerId) && 
                    $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
                {
                    if ($this->skipNextEvent)
                    {
                        $this->skipNextEvent = false;
                        $owner->IsUpdated = true;
                        return;
                    }

                    if ($this->redHandsInPlay($event->theah))
                    {
                        $this->challengeIssuedEvent = clone $event;
                        unset($this->challengeIssuedEvent->theah);
                        $this->inPlayRedHand = true;                    
                        $owner->IsUpdated = true;

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                    else if ($this->thugsInHand($event->theah))
                    {
                        $this->challengeIssuedEvent = clone $event;
                        unset($this->challengeIssuedEvent->theah);
                        $this->inHandThug = true;
                        $owner->IsUpdated = true;                        

                        $event->canceled = true;

                        if ($event->batchId)
                        {
                            $event->theah->deleteEventBatch($event->batchId);
                        }

                        $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($reactionTransitionEvent);
                    }
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId != 'pass')
            {
                $owner = $this->getOwningCard($event->theah);
                $this->clearEvents($event->theah->game);
                $this->inHandThug = false;
                $this->inPlayRedHand = false;
                $owner->IsUpdated = true;
            }
        }
    }

    private function payCost(Game $game, string $reactionId): void
    {
        $owner = $this->getOwningCard($game->theah);

        if ($this->inHandThug)
        {
            $characterId = str_replace("discard-", "", $reactionId);
            $character = $game->theah->getCardById($characterId);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($owner->ControllerId, $characterId, $owner->Id);
            $game->theah->queueEvent($discardEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to discard ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $this->inHandThug = false;
            $owner->IsUpdated = true;
        }

        if ($this->inPlayRedHand)
        {
            $characterId = str_replace("destroy-", "", $reactionId);
            $character = $game->theah->getCardById($characterId);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent($character->ControllerId, $characterId, $character->Location);
            $game->theah->queueEvent($destroyEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to destroy ${character_inject_code}.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $this->inPlayRedHand = false;
            $owner->IsUpdated = true;
        }
    }

    private function clearEvents(Game $game)
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

    private function releaseEvent(Game $game)
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

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass')
        {
            // WHY: Thug discard / Red Hand destroy must resolve before pay state
            // queues EventRiskPlayed — otherwise Night of Drinking (01109) can
            // cancel Unyielding Loyalty without the additional cost being paid.
            $this->payCost($game, $reactionId);

            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        if ($reactionId == 'pass')
        {
            $owner = $this->getOwningCard($game->theah);

            if ($this->inHandThug)
            {
                $this->releaseEvent($game);                    
                $this->inHandThug = false;
                $this->skipNextEvent = true;
                $owner->IsUpdated = true;
            }

            if ($this->inPlayRedHand)
            {
                $this->inPlayRedHand = false;
                $owner->IsUpdated = true;

                if ($this->thugsInHand($game->theah))
                {
                    $this->inHandThug = true;
                    $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $game->theah->queueEvent($transitionEvent);
                }
                else
                {
                    $this->releaseEvent($game);
                    $this->skipNextEvent = true;
                }
            }    
        }

        $game->gamestate->nextState('done');
    }
}