<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03072 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Engaged Attachments on Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null || $theah->game->characterIsInDiscardOrLocker($adversary))
        {
            return false;
        }

        return count($this->getEngagedAttachments($theah, $adversary)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary === null)
            {
                return;
            }

            // WHY snapshot: destroy events are queued; Attachments is unchanged mid-loop.
            foreach ($this->getEngagedAttachments($event->theah, $adversary) as $attachment)
            {
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                    $attachment->ControllerId,
                    $attachment->AttachedToId,
                    $attachment->Id
                );
                $event->theah->eventCheck($unequipEvent);
                $event->theah->queueEvent($unequipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent(
                    $attachment->OwnerId,
                    $attachment->Id,
                    $attachment->Location,
                    $owner->Id,
                    $asEffect = true
                );
                $event->theah->queueEvent($discardEvent);
            }
        }
    }

    /**
     * @return Attachment[]
     */
    private function getEngagedAttachments(Theah $theah, Character $character): array
    {
        $attachments = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment === null || $attachment->FakeAttachment || ! $attachment->Engaged)
            {
                continue;
            }
            $attachments[] = $attachment;
        }
        return $attachments;
    }
}
