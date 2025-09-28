<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01018 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Angelo, Draw a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            $game = $event->theah->game;
            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            // $this->setUsed not called because card is destroyed

            $owner->unEquipAllAttachments($game->theah);
            $event = EventFactory::createCharacterDestroyedEvent($owner->ControllerId, $owner->Id, $owner->getInjectCode());
            $game->theah->queueEvent($event);

            $event = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $game->theah->queueEvent($event);
        }
    }
}