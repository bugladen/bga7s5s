<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskClonePropertyTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _01106_RiskClone extends Risk implements IHasActions
{
    use ActionTrait;
    use RiskClonePropertyTrait;

    public int $ClonedCardId = 0;
    public int $ParentCardId = 0;

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

            //Sink the cloned card to the bottom of the controller's faction deck
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($clonedCard->ControllerId, $clonedCard->Id, false);
            $event->theah->queueEvent($sinkEvent);

            //Move Improvising to the Locker
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($this->ControllerId, $this->ParentCardId);
            $event->theah->queueEvent($removeEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($this->ControllerId, $this->ParentCardId);
            $event->theah->queueEvent($lockerEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
