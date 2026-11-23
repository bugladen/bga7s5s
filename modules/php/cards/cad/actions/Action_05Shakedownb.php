<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Blackmail (equipped): Action - Engage this character • Destroy this card.
class Action_05Shakedownb extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Engage this character; Destroy Blackmail";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $attachment = $this->getOwningAttachment($theah);
        if ($attachment === null || ! $attachment->isAttached())
        {
            return false;
        }

        $character = $this->getOwningCharacter($theah);
        if ($character === null || $character->Engaged)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $attachment = $this->getOwningAttachment($event->theah);
            $character = $this->getOwningCharacter($event->theah);

            if ($character === null || $attachment === null || ! $attachment->isAttached())
            {
                return;
            }

            // WHY: Engage is printed before the bullet = cost. Pay before destroy.
            $engageEvent = EventFactory::createCardEngagedEvent(
                $character->ControllerId,
                $character->Id,
                $attachment->Id,
                $this->Id
            );
            $event->theah->queueEvent($engageEvent);

            if ($attachment instanceof IRiskAttachment)
            {
                $attachment->removeRiskAttachment($event->theah);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($character->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
