<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01014 extends CardReaction
{
    private ?EventCardEngaged $engagedEvent = null;
    private ?EventCardEngarded $engardedEvent = null;
    private ?EventCardMoving $cardMovingEvent = null;
    private ?EventCharacterBeingWounded $characterWoundedEvent = null;
    private ?EventCharacterBeingHealed $characterHealedEvent = null;
    private ?EventChallengeIssued $challengeIssuedEvent = null;
    private bool $isChallenger = false;

    private bool $inHandThug = false;
    private bool $inPlayThug = false;
    private bool $moveHome = false;
    private bool $skipNextEvent = false;
     
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Target one of your Thugs instead of Vittoria");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $message = '';
        if ($this->inHandThug)
        {
            $message = $theah->game->translate('${you} may choose a Thug in your Hand to put into play: ');
        }
        if ($this->inPlayThug)
        {
            $message = $theah->game->translate('${you} may choose an In-Play Thug at Vittoria\'s Location to play as the new target: ');
        }

        if ($this->moveHome)
        {
            $message = $theah->game->translate('${you} may choose to move Vittoria Home: ');
        }

        return parent::getReactionDescription($theah) . $message;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($this->inHandThug)
        {
            $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            $cards = array_filter($cards, fn($card) => $card->hasTrait("Thug"));
            foreach ($cards as $card)
            {
                $array[] = $this->createButtonProperty($theah->game, $card->Name, "putIntoPlay-$card->Id");
            }
        }
        if ($this->inPlayThug)
        {
            $characters = $theah->getCharactersAtLocation($owner->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId && $character->hasTrait("Thug"));
            foreach ($characters as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, $character->Name, "putIntoPlay-$character->Id");
            }
        }
        if ($this->moveHome)
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Move Home'), 'moveHome');
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    private function shouldReactToEvent(Theah $theah, int $sourceId, string $abilityId): bool{
        $source = $theah->getCardById($sourceId);
        if ($source)
        {
            $ability = $source->getAbilityById($abilityId);
            if ($ability instanceof IAbilityThatTargetsCharacters)
            {
                return true;
            }
        }

        $action = $theah->getInPlayActionById($abilityId);
        if ($action instanceof IAbilityThatTargetsCharacters)
        {
            return true;
        }

        return false;
    }

    private function thugsInHand(Theah $theah): bool
    {
        $owner = $this->getOwningCharacter($theah);
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        $hand = array_filter($hand, fn($card) => $card->hasTrait("Thug"));
        return count($hand) > 0;
    }

    private function thugsInPlay(Theah $theah): bool
    {
        $owner = $this->getOwningCharacter($theah);
        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId && $character->hasTrait("Thug"));
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardEngaged && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner->Id == $event->cardId && $owner->ControllerId != $event->playerId && 
                $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                if ($this->thugsInHand($event->theah))
                {
                    $this->engagedEvent = clone $event;
                    unset($this->engagedEvent->theah);
                    $this->inHandThug = true;                    
                    $owner->IsUpdated = true;

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
                else if ($this->thugsInPlay($event->theah))
                {
                    $this->engagedEvent = clone $event;
                    unset($this->engagedEvent->theah);
                    $this->inPlayThug = true;
                    $owner->IsUpdated = true;                        

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventCardEngarded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner->Id == $event->cardId && $owner->ControllerId != $event->playerId && 
                $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                if ($this->thugsInHand($event->theah))
                {
                    $this->engardedEvent = clone $event;
                    unset($this->engardedEvent->theah);
                    $this->inHandThug = true;                    
                    $owner->IsUpdated = true;

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
                else if ($this->thugsInPlay($event->theah))
                {
                    $this->engardedEvent = clone $event;
                    unset($this->engardedEvent->theah);
                    $this->inPlayThug = true;
                    $owner->IsUpdated = true;                        

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventCardMoving && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner->Id == $event->cardId && $owner->ControllerId != $event->initiatingPlayerId && 
                $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                if ($this->thugsInHand($event->theah))
                {
                    $this->cardMovingEvent = clone $event;
                    unset($this->cardMovingEvent->theah);
                    $this->inHandThug = true;                    
                    $owner->IsUpdated = true;

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
                else if ($this->thugsInPlay($event->theah))
                {
                    $this->cardMovingEvent = clone $event;
                    unset($this->cardMovingEvent->theah);
                    $this->inPlayThug = true;
                    $owner->IsUpdated = true;                        

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventCharacterBeingWounded && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $source = $event->theah->getCardById($event->sourceId);
            if ($owner->Id == $event->characterId && $owner->ControllerId != $source->ControllerId && 
                $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                if ($this->thugsInHand($event->theah))
                {
                    $this->characterWoundedEvent = clone $event;
                    unset($this->characterWoundedEvent->theah);
                    $this->inHandThug = true;                    
                    $owner->IsUpdated = true;

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
                else if ($this->thugsInPlay($event->theah))
                {
                    $this->characterWoundedEvent = clone $event;
                    unset($this->characterWoundedEvent->theah);
                    $this->inPlayThug = true;
                    $owner->IsUpdated = true;                        

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventCharacterBeingHealed && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $source = $event->theah->getCardById($event->sourceId);
            if ($owner->Id == $event->characterId && $owner->ControllerId != $source->ControllerId && 
                $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                if ($this->thugsInHand($event->theah))
                {
                    $this->characterHealedEvent = clone $event;
                    unset($this->characterHealedEvent->theah);
                    $this->inHandThug = true;                    
                    $owner->IsUpdated = true;

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
                else if ($this->thugsInPlay($event->theah))
                {
                    $this->characterHealedEvent = clone $event;
                    unset($this->characterHealedEvent->theah);
                    $this->inPlayThug = true;
                    $owner->IsUpdated = true;                        

                    $event->canceled = true;

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && !$event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $source = $event->theah->getCardById($event->sourceId);
            $initiatingControllerId = $source ? $source->ControllerId : $event->playerId;
            if (($owner->Id == $event->challengerId || $owner->Id == $event->defenderId) && $owner->ControllerId != $initiatingControllerId && 
                $this->shouldReactToEvent($event->theah, $event->sourceId, $event->abilityId))
            {
                if ($this->skipNextEvent)
                {
                    $this->skipNextEvent = false;
                    $owner->IsUpdated = true;
                    return;
                }

                if ($this->thugsInHand($event->theah))
                {
                    $this->challengeIssuedEvent = clone $event;
                    unset($this->challengeIssuedEvent->theah);
                    $this->inHandThug = true;                    
                    $owner->IsUpdated = true;

                    $event->canceled = true;
                    if ($owner->Id == $event->challengerId)
                    {
                        $this->isChallenger = true;
                    }

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
                else if ($this->thugsInPlay($event->theah))
                {
                    $this->challengeIssuedEvent = clone $event;
                    unset($this->challengeIssuedEvent->theah);
                    $this->inPlayThug = true;
                    $owner->IsUpdated = true;                        

                    $event->canceled = true;
                    if ($owner->Id == $event->challengerId)
                    {
                        $this->isChallenger = true;
                    }

                    $reactionTransitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($reactionTransitionEvent);
                }
            }
        }
    }

    private function releaseEvent(Game $game, int $characterId)
    {
        if ($this->engagedEvent)
        {
            $this->engagedEvent->cardId = $characterId;
            $game->theah->queueEvent($this->engagedEvent);
            $this->engagedEvent = null;
        }

        if ($this->engardedEvent)
        {
            $this->engardedEvent->cardId = $characterId;
            $game->theah->queueEvent($this->engardedEvent);
            $this->engardedEvent = null;
        }

        if ($this->cardMovingEvent)
        {
            $this->cardMovingEvent->cardId = $characterId;
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
        }
        
        if ($this->characterWoundedEvent)
        {
            $this->characterWoundedEvent->characterId = $characterId;
            $game->theah->queueEvent($this->characterWoundedEvent);
            $this->characterWoundedEvent = null;
        }
        
        if ($this->characterHealedEvent)
        {
            $this->characterHealedEvent->characterId = $characterId;
            $game->theah->queueEvent($this->characterHealedEvent);
            $this->characterHealedEvent = null;
        }

        if ($this->challengeIssuedEvent)
        {
            if ($this->isChallenger)
            {
                $game->globals->set(GAME::CHOSEN_PERFORMER, $characterId);
                $this->challengeIssuedEvent->challengerId = $characterId;
            }
            else
            {
                $game->globals->set(GAME::CHOSEN_TARGET, $characterId);
                $this->challengeIssuedEvent->defenderId = $characterId;
            }
            $game->theah->queueEvent($this->challengeIssuedEvent);
            $this->isChallenger = false;
            $this->challengeIssuedEvent = null;
        }
    }



    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($this->moveHome)
        {
            if ($reactionId != 'decline')
            {
                $owner = $this->getOwningCharacter($game->theah);
                $event = EventFactory::createCardMovingEvent($owner->ControllerId, $owner->Id, $owner->Location, Game::LOCATION_PLAYER_HOME, $engage=false, $owner->Id);
                $game->theah->queueEvent($event);

                $this->moveHome = false;
                $owner->IsUpdated = true;
            }

            $this->setUsed($game->theah, true);
        }

        if ($this->inPlayThug)
        {
            if ($reactionId != 'decline')
            {
                $owner = $this->getOwningCharacter($game->theah);
                $characterId = str_replace("putIntoPlay-", "", $reactionId);
                $character = $game->theah->getCharacterById($characterId);
                
                $this->releaseEvent($game, $characterId);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to use ${character_inject_code} (same location) as the new target.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $game->theah->queueEvent($transitionEvent);

                $this->inPlayThug = false;
                $this->moveHome = true;
                $owner->IsUpdated = true;
            }

            if ($reactionId == 'decline')
            {
                $owner = $this->getOwningCharacter($game->theah);
                $this->releaseEvent($game, $owner->Id);
                $this->inPlayThug = false;
                $this->skipNextEvent = true;
                $owner->IsUpdated = true;

                $this->setUsed($game->theah, true);
            }
        }

        if ($this->inHandThug)
        {
            if ($reactionId != 'decline')
            {
                $owner = $this->getOwningCharacter($game->theah);
                $characterId = str_replace("putIntoPlay-", "", $reactionId);
                $character = $game->theah->getCharacterById($characterId);
                $musterEvent = EventFactory::createCharacterMusteredEvent($owner->ControllerId, $characterId, $owner->Location);
                $game->theah->queueEvent($musterEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to put ${character_inject_code} into play.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $this->inHandThug = false;
                $this->inPlayThug = true;
                $owner->IsUpdated = true;

                $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $game->theah->queueEvent($transitionEvent);

            }

            if ($reactionId == 'decline')
            {
                $owner = $this->getOwningCharacter($game->theah);
                $this->inHandThug = false;
                $this->inPlayThug = true;
                $owner->IsUpdated = true;

                $characters = $game->theah->getCharactersAtLocation($owner->Location);
                $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId && $character->hasTrait("Thug"));
                if (count($characters) > 0)
                {
                    $this->inPlayThug = true;
                    $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $game->theah->queueEvent($transitionEvent);                    
                }
                else
                {
                    $this->releaseEvent($game, $owner->Id);  // Add this line!
                    $this->skipNextEvent = true;              // Add this to prevent re-triggering
                    $this->setUsed($game->theah, true);
                }
            }
        }

        $game->gamestate->nextState("done");
    }
}