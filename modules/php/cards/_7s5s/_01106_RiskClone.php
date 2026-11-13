<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskClonePropertyTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _01106_RiskClone extends Risk implements IHasActions
{
    use ActionTrait;
    use RiskClonePropertyTrait;

    public int $ClonedCardId = 0;
    public int $ParentCardId = 0;
    // WHY: Card text — "After it resolves, sink it." Discard-from-hand is pay-time, not
    // resolve-time. Multi-step stolen actions (Press the Advantage 01058) still need this
    // shell in buildCity (discard) so their handleEvent sees CharacterTargeted and can
    // queue engage/move. Permanently hiding on discard left only the act() notify
    // ("chosen to engage…") with no board move.
    public bool $PendingCleanup = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardDiscardedFromHand && $event->cardId == $this->Id)
        {
            $this->PendingCleanup = true;
            $this->IsUpdated = true;
            return;
        }

        if (
            $event instanceof EventActionResolved
            && $this->PendingCleanup
            && $event->playerId == $this->ControllerId
        )
        {
            // WHY CHOSEN_ACTION prefix: only clean up when *this* shell's stolen action
            // is the one resolving. Another ActionResolved for the same player (or a
            // stale global) must not sink/locker early.
            $chosenAction = (string) $event->theah->game->globals->get(Game::CHOSEN_ACTION, '');
            if ($chosenAction === '' || ! str_starts_with($chosenAction, $this->Id . '_'))
            {
                return;
            }

            $this->queueImprovisingCleanup($event);
        }
    }

    private function queueImprovisingCleanup(Event $event): void
    {
        $this->PendingCleanup = false;
        $this->IsUpdated = true;

        $game = $event->theah->game;

        // WHY permanentlyHide via queued event, not moveCard here:
        // When cleanup ran on discard, EventCardDiscardedFromHand has
        // runEventHubAfterCards=true — card handler before hub discard write. An
        // immediate Permanently Hidden write was overwritten by that hub move, leaving
        // the Adaptable-looking shell in the owner's discard to reshuffle (Unique
        // bypass: card_type is 01106_RiskClone; no Maneuvers; ControllerId stayed the
        // Improvising thief). Cleanup now runs on ActionResolved (shell already in
        // Owner discard); permanentlyHide on the remove event is still the safe hide.
        // WHY OwnerId: EventHub routes discard to OwnerId's pile; remove must match.
        $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent(
            $this->OwnerId,
            $this->Id,
            $messageHidden = false,
            $permanentlyHide = true
        );
        $event->theah->queueEvent($removeEvent);

        // Sink the cloned card to the bottom of the owner's faction deck
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

        // Move Improvising to the Locker
        if ($this->ParentCardId > 0)
        {
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($this->ControllerId, $this->ParentCardId);
            $event->theah->queueEvent($removeEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($this->ControllerId, $this->ParentCardId);
            $event->theah->queueEvent($lockerEvent);
        }

        // WHY no createActionResolvedEvent here: cleanup is driven by the stolen
        // action's (or UL cancel's) ActionResolved. Queuing another would double-fire.
    }
}
