<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskClonePropertyTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _04cd01_RiskClone extends Risk implements IHasActions
{
    use ActionTrait;
    use RiskClonePropertyTrait;

    public int $ClonedCardId = 0;
    // WHY: Card text — "After it resolves, sink it." Discard-from-hand is pay-time, not
    // resolve-time. Multi-step stolen actions (Move Along 01056) still need this shell
    // in buildCity (discard) so actFromCardWithId / getInPlayActionById find Action_01056
    // on the defender's move-Home chooser. Hiding on discard left TRANSITION_* unrestorable
    // after technique/ChallengeIssued reactions — stuck on highDramaPhase01056_2.
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
            // is the one resolving. Another ActionResolved for the same player must not
            // sink/hide early.
            $chosenAction = (string) $event->theah->game->globals->get(Game::CHOSEN_ACTION, '');
            if ($chosenAction === '' || ! str_starts_with($chosenAction, $this->Id . '_'))
            {
                return;
            }

            $this->queuePenyaCleanup($event);
        }
    }

    private function queuePenyaCleanup(Event $event): void
    {
        $this->PendingCleanup = false;
        $this->IsUpdated = true;

        $game = $event->theah->game;

        // WHY permanentlyHide queued (not moveCard here): see _01106_RiskClone —
        // discard hub runs after cards and would undo an immediate hide. Cleanup now
        // runs on ActionResolved (shell already in Owner discard).
        // WHY OwnerId: EventHub routes discard to OwnerId's pile; remove must match.
        $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent(
            $this->OwnerId,
            $this->Id,
            $messageHidden = false,
            $permanentlyHide = true
        );
        $event->theah->queueEvent($removeEvent);

        // Sink the cloned risk to the bottom of the owner's faction deck
        // (card text: "After it resolves, sink it." / sunk cards return to owner).
        if ($this->ClonedCardId > 0)
        {
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            if ($clonedCard !== null)
            {
                $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($clonedCard->OwnerId, $clonedCard->Id, false);
                $event->theah->queueEvent($sinkEvent);
            }
        }

        // WHY: Attachment was already sunk as the City Action cost at commit time.
        // WHY no createActionResolvedEvent here: cleanup is driven by the stolen
        // action's (or challenge cancel's) ActionResolved. Queuing another would double-fire.
    }
}
