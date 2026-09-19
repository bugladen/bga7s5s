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
            //Remove the clone from the owner's discard pile and hide it
            // WHY OwnerId: EventHub routes discard to OwnerId's pile; remove must match.
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($this->OwnerId, $this->Id);
            $event->theah->queueEvent($removeEvent);
            $game->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN, 0, $this);

            //Sink the cloned card to the bottom of the owner's faction deck
            // WHY OwnerId: Improvising steals control; ownership is unchanged. Card text:
            // "Cards return to their owner's deck when sunk."
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($clonedCard->OwnerId, $clonedCard->Id, false);
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
