<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01199 extends CardReaction
{

    private int $TargetCharacterId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Heal Wound');

        $this->TargetCharacterId = 0;

    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Heal Wound: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal Wound'), 'healWound');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelEnd && $this->isAvailable())
        {
            $takama = $this->getOwningCharacter($event->theah);
            if ($takama->isControlled())
            {
                $challenger = $event->theah->getCharacterById($event->challengerId);
                $defender = $event->theah->getCharacterById($event->defenderId);
    
                if ($challenger->Location == $takama->Location && 
                    $takama->ControllerId == $challenger->ControllerId && 
                    $challenger->Wounds > 0)
                {
                    $this->TargetCharacterId = $challenger->Id;
                    $takama->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($takama->ControllerId, $takama->Id, $this->Id);
                    $event->queueEvent($transition);
                }
                else if ($defender->Location == $takama->Location && 
                    $takama->ControllerId == $defender->ControllerId && 
                    $defender->Wounds > 0)
                {
                    $this->TargetCharacterId = $defender->Id;
                    $takama->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($takama->ControllerId, $takama->Id, $this->Id);
                    $event->queueEvent($transition);
                }
            }
        }   
        
        if ($event instanceof EventDuskEndOfDay)
        {
            $takama = $this->getOwningCharacter($event->theah);
            $this->TargetCharacterId = 0;
            $takama->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'healWound')
        {
            $this->setUsed($game->theah, true);

            $takama = $this->getOwningCharacter($game->theah);
            $event = EventFactory::createCharacterBeingHealedEvent($this->TargetCharacterId, $takama->Id, 1, $takama->getInjectCode(), $this->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $this->TargetCharacterId = 0;
            $takama->IsUpdated = true;
        }

        if ($reactionId == 'pass')
        {
            $this->TargetCharacterId = 0;
            $takama = $this->getOwningCharacter($game->theah);
            $takama->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
