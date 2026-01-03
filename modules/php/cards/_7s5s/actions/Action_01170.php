<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_01170 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard Your Hand. Draw a Card");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            foreach ($hand as $card)
            {
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->ControllerId, $card->Id, $owner->Id);
                $event->theah->queueEvent($discardEvent);
            }

            $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $event->theah->queueEvent($drawEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}