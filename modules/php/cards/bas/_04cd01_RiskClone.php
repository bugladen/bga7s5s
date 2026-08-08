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
            // Remove the clone from the discard pile and hide it
            $game = $event->theah->game;
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->ownerId, $this->Id);
            $event->theah->queueEvent($removeEvent);
            $game->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN, 0, $this);

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
