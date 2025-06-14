<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01201 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = " Wound and draw a card";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $ravenna = $this->getOwningCard($theah);

        if (! in_array("Sorcerer", $ravenna->Traits))
        {
            return false;
        }

        return $theah->cardInCity($ravenna);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $event->theah->game->notifyAllPlayers('message', clienttranslate('${player_name} is performing Ravenna\'s action to wound Ravenna and draw a card'), [
                'player_name' => $event->theah->game->getActivePlayerName()
            ]);

            $this->setUsed($event->theah, true);

            $ravenna = $this->getOwningCard($event->theah);

            $woundEvent = EventFactory::createCharacterWoundedEvent($ravenna->Id, $ravenna->Id, 1, $event->theah->game->translate("Ravenna: Wound and draw card action"));
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);

            $card = $event->theah->game->playerDrawCard($event->playerId);
            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, $card, $event->theah->game->translate("<strong>Ravenna: Wound and draw card action</strong>"));
            $event->theah->queueEvent($addEvent);
        }
    }
}