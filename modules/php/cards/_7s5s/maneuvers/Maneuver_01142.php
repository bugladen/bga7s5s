<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01142 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Target Attachment on Adversary");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $adversary = $theah->getDuelRoundOpponent();

        $attachmentCount = count($adversary->Attachments);
        return $actor->ModifiedCombat >= $adversary->ModifiedCombat && $attachmentCount > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01142", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01142)
        {
            $adversary = $game->theah->getDuelRoundOpponent();

            $attachments = [];
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                $attachments[] = ["id" => $attachment->Id, "name" => $attachment->Name];
            }

            $args["attachments"] = $attachments;
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01142)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if (! $attachment)
            {
                throw new \BgaUserException($game->translate("Invalid attachment"));
            }

            $adversary = $game->theah->getDuelRoundOpponent();
            if ( ! in_array($attachment->Id, $adversary->Attachments))
            {
                throw new \BgaUserException($game->translate("Attachment is not on the adversary"));
            }

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($adversary->ControllerId, $adversary->Id, $attachment->Id);
            $game->theah->queueEvent($unequipEvent);

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($adversary->ControllerId, $attachment->Id, $adversary->Location, $owner->Id, $asEffect = true);
            $game->theah->queueEvent($discardEvent);            

            $game->gamestate->nextState();
        }
    }
}