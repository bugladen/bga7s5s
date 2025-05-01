<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01182 extends CardReaction
{
    public int $TargetCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Id = 'Reaction_01182';
        $this->Name = 'Wound Character';
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . '${you} may choose to Wound Character leaving Ekko\'s Location: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, 'Wound Character', 'woundCharacter');
        $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardMoved && $this->isAvailable())
        {
            $ekko = $this->getOwningCard($event->theah);
            $card = $event->theah->getCardById($event->cardId);

            if ($card instanceof Character && 
                $event->theah->cardInCity($ekko) &&
                $ekko->ControllerId != $card->ControllerId && 
                $ekko->Location == $event->fromLocation)
            {
                $this->TargetCharacterId = $event->cardId;
                $this->setUsed($event->theah, true);
                $transition = EventFactory::createReactionTransitionEvent($ekko->ControllerId, $ekko->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundCharacter')
        {
            $ekko = $this->getOwningCard($game->theah);
            $woundEvent = EventFactory::createCharacterWoundedEvent($this->TargetCharacterId, $ekko->Id, 1, "Left Eko Sorridi's Location");
            $game->theah->queueEvent($woundEvent);
    
            $this->TargetCharacterId = 0;
            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");        
    }

}