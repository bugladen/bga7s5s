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

class _01124_RiskClone extends Risk implements IHasActions
{
    use ActionTrait;
    use RiskClonePropertyTrait;

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
            // WHY permanentlyHide queued (not moveCard here): see _01106_RiskClone —
            // discard hub runs after cards and would undo an immediate hide.
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent(
                $event->ownerId,
                $this->Id,
                $messageHidden = true,
                $permanentlyHide = true
            );
            $event->theah->queueEvent($removeEvent);

            //Move the cloned card to the locker
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $lockerEvent = EventFactory::createCardSentToLockerEvent($clonedCard->ControllerId, $clonedCard->Id);
            $event->theah->queueEvent($lockerEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
