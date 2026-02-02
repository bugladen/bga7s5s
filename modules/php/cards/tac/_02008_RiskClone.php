<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;

class _02008_RiskClone extends FactionAttachment
{
    public int $ClonedCardId = 0;    

    public function __construct()
    {
        parent::__construct();

        $this->FaceDown = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardDiscardedFromPlay && $event->cardId == $this->Id)
        {
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($event->ownerId, $this->Id, $messageHidden = true, $permanentlyHide = true);
            $event->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createCardAddedToHandEvent($event->ownerId, $this->ClonedCardId, $hidden = true);
            $event->theah->queueEvent($addEvent);
        }
    }

}
