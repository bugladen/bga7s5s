<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01182 extends CardReaction
{
    public array $TargetCharacterIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Wound Character');
    }

    public function getReactionDescription(Theah $theah): string
    {
        $character = $theah->getCharacterById($this->TargetCharacterIds[0]);
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may choose to Wound %s leaving Eko\'s Location: '), $character->Name);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Character'), 'woundCharacter');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoving && $this->isAvailable())
        {
            $ekko = $this->getOwningCharacter($event->theah);
            if ($ekko->isControlled())
            {
                $card = $event->theah->getCardById($event->cardId);

                if ($card instanceof Character && 
                    $event->theah->cardInCity($ekko) &&
                    $ekko->ControllerId != $card->ControllerId && 
                    $ekko->Location == $event->fromLocation)
                {
                    $this->TargetCharacterIds[] = $event->cardId;
                    $ekko->IsUpdated = true;
                    $transition = EventFactory::createReactionTransitionEvent($ekko->ControllerId, $ekko->Id, $this->Id);
                    $event->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundCharacter')
        {
            $targetId = array_shift($this->TargetCharacterIds);
            $this->TargetCharacterIds = [];

            $ekko = $this->getOwningCard($game->theah);
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($targetId, $ekko->Id, 1, $ekko->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->theah->deleteTransitionEvents($this->Id);
            $ekko->IsUpdated = true;
            $this->setUsed($game->theah, true);
        }

        if ($reactionId == 'pass')
        {
            array_shift($this->TargetCharacterIds);
            $ekko = $this->getOwningCard($game->theah);
            $ekko->IsUpdated = true;
        }

        $game->gamestate->nextState("done");        
    }

}