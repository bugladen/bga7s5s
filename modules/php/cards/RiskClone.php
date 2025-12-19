<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class RiskClone extends Risk implements IHasActions
{
    use ActionTrait;

    public int $ClonedCardId = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardDiscardedFromHand && $event->cardId == $this->Id)
        {
            //Remove the clone from the discard pile and hide it
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->ownerId, $this->Id);
            $event->theah->queueEvent($removeEvent);
            $deck = $game->getGameDeckObject();
            $deck->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            //Move the cloned card to the locker
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $lockerEvent = EventFactory::createCardSentToLockerEvent($clonedCard->ControllerId, $clonedCard->Id);
            $event->theah->queueEvent($lockerEvent);

        }
    }
}
