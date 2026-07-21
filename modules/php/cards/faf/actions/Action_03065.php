<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03065 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Sink Lodestone: Move Performer Home");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || ! $theah->cardInCity($owner))
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
            $owner = $this->getOwningCharacter($event->theah);
            if ($attachment === null || $owner === null)
            {
                return;
            }

            // Cost: Sink this card (unequip → remove from play → bottom of faction deck).
            // Mirror Technique_02055 (Dame of Swords).
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                $attachment->ControllerId,
                $owner->Id,
                $attachment->Id
            );
            $event->theah->queueEvent($unequipEvent);

            $removedEvent = EventFactory::createCardRemovedFromPlayEvent(
                $attachment->ControllerId,
                $attachment->Id,
                $attachment->Location
            );
            $event->theah->queueEvent($removedEvent);

            $sinkEvent = EventFactory::createCardAddedToFactionDeckEvent(
                $attachment->OwnerId,
                $attachment->Id,
                false
            );
            $event->theah->queueEvent($sinkEvent);

            // Effect: Move your performer Home. Own ability — Lodestone condition does not block.
            // engage=false: sink cost already paid; move itself is not an engage cost.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                Game::LOCATION_PLAYER_HOME,
                false,
                $attachment->Id,
                $this->Id
            );
            $event->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
