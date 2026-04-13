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

    private int $ChallengerId;
    private int $DefenderId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Heal Wound');

        $this->ChallengerId = 0;
        $this->DefenderId = 0;

    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Heal Wound: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->ChallengerId != 0)
        {
            $character = $theah->getCharacterById($this->ChallengerId);
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Heal %s'), $theah->game->translate($character->Name)), "healWound-{$this->ChallengerId}");
        }
        if ($this->DefenderId != 0)
        {
            $character = $theah->getCharacterById($this->DefenderId);
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Heal %s'), $theah->game->translate($character->Name)), "healWound-{$this->DefenderId}");
        }

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

                $hasValidTarget = false;

                if ($challenger->Location == $takama->Location && 
                    $takama->ControllerId == $challenger->ControllerId && 
                    $challenger->Wounds > 0)
                {
                    $this->ChallengerId = $challenger->Id;
                    $hasValidTarget = true;
                }

                if ($defender->Location == $takama->Location && 
                    $takama->ControllerId == $defender->ControllerId && 
                    $defender->Wounds > 0)
                {
                    $this->DefenderId = $defender->Id;
                    $hasValidTarget = true;
                }

                if ($hasValidTarget)
                {
                    $takama->IsUpdated = true;
                    $transition = EventFactory::createReactionTransitionEvent($takama->ControllerId, $takama->Id, $this->Id);
                    $event->queueEvent($transition);
                }
            }
        }   
        
        if ($event instanceof EventDuskEndOfDay)
        {
            $takama = $this->getOwningCharacter($event->theah);
            $this->ChallengerId = 0;
            $this->DefenderId = 0;
            $takama->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if (str_starts_with($reactionId, 'healWound'))
        {
            $this->setUsed($game->theah, true);

            $targetId = (int) str_replace("healWound-", "", $reactionId);

            $takama = $this->getOwningCharacter($game->theah);
            $event = EventFactory::createCharacterBeingHealedEvent($targetId, $takama->Id, 1, $takama->getInjectCode(), $this->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $this->ChallengerId = 0;
            $this->DefenderId = 0;
            $takama->IsUpdated = true;
        }

        if ($reactionId == 'pass')
        {
            $this->ChallengerId = 0;
            $this->DefenderId = 0;
            $takama = $this->getOwningCharacter($game->theah);
            $takama->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
