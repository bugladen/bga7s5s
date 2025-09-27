<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01201 extends CharacterAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound and Draw a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $ravenna = $this->getOwningCard($theah);

        if (! $ravenna->isControlled())
        {
            return false;
        }

        if (! $ravenna->hasTrait("Sorcerer"))
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
            $event->theah->game->notifyAllPlayers('message', clienttranslate('${player_name} performed Ravenna\'s action to wound Ravenna and draw a card'), [
                'player_name' => $event->theah->game->getActivePlayerName()
            ]);

            $this->setUsed($event->theah, true);

            $ravenna = $this->getOwningCard($event->theah);

            $woundEvent = EventFactory::createCharacterWoundedEvent($ravenna->Id, $ravenna->Id, 1, $ravenna->getInjectCode());
            $event->theah->eventCheck($woundEvent);
            $event->theah->queueEvent($woundEvent);

            $card = $event->theah->game->playerDrawCard($event->playerId);
            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, $card, $ravenna->getInjectCode());
            $event->theah->queueEvent($addEvent);
        }
    }
}