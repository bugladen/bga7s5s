<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01046b extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal a Wound");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $darkGift = $this->getOwningCard($theah);
        if ($darkGift->Engaged)
        {
            return false;
        }

        $attachedTo = $this->getOwningCharacter($theah);

        return $attachedTo->Wounds > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $darkGift = $this->getOwningCard($event->theah);
            $attachedTo = $this->getOwningCharacter($event->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($darkGift->ControllerId, $darkGift->Id, $darkGift->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            $healEvent = EventFactory::createCharacterBeingHealedEvent($attachedTo->Id, $darkGift->Id, 1, sprintf($event->theah->game->translate("%s Action"), $darkGift->getInjectCode()), $this->Id);
            $event->theah->queueEvent($healEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($attachedTo->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}