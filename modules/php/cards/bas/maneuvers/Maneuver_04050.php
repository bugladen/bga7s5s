<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_04050 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Thrust, Engage Adversary and Attachments");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null && $actor->hasTrait('Duelist');
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s adds 1 Thrust."),
                $owner->getInjectCode()
            );
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($adversary === null || $event->theah->game->characterIsInDiscardOrLocker($adversary))
            {
                return;
            }

            // WHY skip already Engaged: hub always notifies; re-engage spam is noise.
            // +1 Thrust still applied in calc — do not grey availability on engage state.
            if (! $adversary->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent(
                    $owner->ControllerId,
                    $adversary->Id,
                    $owner->Id,
                    $this->Id
                );
                $event->theah->queueEvent($engageEvent);
            }

            foreach ($this->getUnengagedAttachments($event->theah, $adversary) as $attachment)
            {
                $engageEvent = EventFactory::createCardEngagedEvent(
                    $owner->ControllerId,
                    $attachment->Id,
                    $owner->Id,
                    $this->Id
                );
                $event->theah->queueEvent($engageEvent);
            }
        }
    }

    /**
     * @return Attachment[]
     */
    private function getUnengagedAttachments(Theah $theah, Character $character): array
    {
        $attachments = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            // WHY skip FakeAttachment: RiskAttachments / internal fakes are not "equipped attachments".
            if ($attachment === null || $attachment->FakeAttachment || $attachment->Engaged)
            {
                continue;
            }
            $attachments[] = $attachment;
        }
        return $attachments;
    }
}
