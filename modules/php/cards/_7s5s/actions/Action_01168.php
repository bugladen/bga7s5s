<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01168 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Spend a Reknown, Draw a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $reknown = $theah->game->getPlayerReknown($playerId);

        return $reknown >= 1;
    }

    public function handleEvent(Event $event): void
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $reknownEvent = EventFactory::createPlayerLosesReknownEvent($event->playerId, 1);
            $event->theah->queueEvent($reknownEvent);

            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);

            $card = $game->playerDrawCard($event->playerId);
            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, $card, sprintf($game->translate("%s effect"), $owner->getInjectCode()));
            $game->theah->queueEvent($addEvent);

            $card = $game->playerDrawCard($event->playerId);
            $addEvent = EventFactory::createCardDrawnEvent($event->playerId, $card, sprintf($game->translate("%s effect"), $owner->getInjectCode()));
            $game->theah->queueEvent($addEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($event->playerId, $owner->Id);
            $game->theah->queueEvent($lockerEvent);
        }
    }
}