<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\RiskClonePropertyTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;

class _04cd01_RiskClone extends Risk implements IHasActions
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
                $messageHidden = false,
                $permanentlyHide = true
            );
            $event->theah->queueEvent($removeEvent);

            // Sink the cloned risk to the bottom of the controller's faction deck
            // (card text: "After it resolves, sink it.")
            $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
            $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent($clonedCard->ControllerId, $clonedCard->Id, false);
            $event->theah->queueEvent($sinkEvent);

            // WHY: Attachment was already sunk as the City Action cost at commit time.
            // Only createActionResolvedEvent remains here (mirrors _01106_RiskClone timing).
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($this->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
