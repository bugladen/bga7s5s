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
            // WHY permanentlyHide via queued event, not moveCard here:
            // EventCardDiscardedFromHand has runEventHubAfterCards=true — this handler
            // runs BEFORE the hub moves the shell into OwnerId's discard. An immediate
            // Permanently Hidden write was overwritten by that hub move, leaving the
            // Adaptable-looking shell in the owner's discard to reshuffle into the
            // faction deck (Unique bypass: card_type is 01106_RiskClone; no Maneuvers;
            // ControllerId stayed the Improvising thief).
            // WHY OwnerId: EventHub routes discard to OwnerId's pile; remove must match.
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent(
                $this->OwnerId,
                $this->Id,
                $messageHidden = false,
                $permanentlyHide = true
            );
            $event->theah->queueEvent($removeEvent);

            //Sink the cloned card to the bottom of the owner's faction deck
            // WHY OwnerId: Improvising steals control; ownership is unchanged. Card text:
            // "Cards return to their owner's deck when sunk."
            // WHY ClonedCardId guard: harness/partial cleanup must not fatal on unset id.
            if ($this->ClonedCardId > 0)
            {
                $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
                if ($clonedCard !== null)
                {
                    $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($clonedCard->OwnerId, $clonedCard->Id, false);
                    $event->theah->queueEvent($sinkEvent);
                }
            }

            //Move Improvising to the Locker
            if ($this->ParentCardId > 0)
            {
                $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($this->ControllerId, $this->ParentCardId);
                $event->theah->queueEvent($removeEvent);

                $lockerEvent = EventFactory::createCardSentToLockerEvent($this->ControllerId, $this->ParentCardId);
                $event->theah->queueEvent($lockerEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
