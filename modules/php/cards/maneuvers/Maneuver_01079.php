<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGetCostForManeuverFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01079 extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Adversary's Weapon");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);

        $actor = $theah->getDuelRoundActor();
        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);

        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment->hasTrait("Weapon"))
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelGetCostForManeuverFromHand && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            $adversary = $event->theah->getCharacterById($event->adversaryId);

            if ($actor->ModifiedFinesse > $adversary->ModifiedFinesse)
            {
                $event->discount += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s reduces the cost of Maneuver by 1 because your participant has a higher Finesse Stat."), $owner->Name);
            }
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01079", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01079)
        {
            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);
    
            $attachments = [];
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment->hasTrait("Weapon"))
                {
                    $attachments[] = ["id" => $attachment->Id, "name" => $attachment->Name];
                }
            }

            $args["attachments"] = $attachments;
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01079)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null)
            {
                throw new \BgaUserException($game->translate("Invalid attachment ID."));
            }

            $attachmentOwnerId = $attachment->AttachedToId;
            
            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);

            if ($attachmentOwnerId != $adversaryId)
            {
                throw new \BgaUserException($game->translate("Attachment is not equipped to Adversary."));
            }

            $game->notifyAllPlayers("message", clienttranslate('<strong>Disarm:</strong> ${player_name} has chosen <strong>${attachment_name}</strong>.'), [
                "player_name" => $game->getActivePlayerName(),
                "attachment_name" => $attachment->Name,
            ]);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            $owner = $this->getOwningCard($game->theah);
            $transitionEvent = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "01079_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState();
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_01079_2)
        {
            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);

            //Destroy Weapon
            if ($id == 1)
            {
                $attachmentId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
                $attachment = $game->theah->getAttachmentById($attachmentId);

                $unquipEvent = EventFactory::createAttachmentUnequippedEvent($adversary->ControllerId, $adversary->Id, $attachmentId);
                $game->theah->eventCheck($unquipEvent);
                $game->theah->queueEvent($unquipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($adversary->ControllerId, $attachmentId, $adversary->Location);
                $game->theah->eventCheck($discardEvent);
                $game->theah->queueEvent($discardEvent);

                $game->notifyAllPlayers("message", clienttranslate('<strong>Disarm:</strong> ${player_name} has destroyed <strong>${attachment_name}</strong>.'), [
                    "player_name" => $game->getActivePlayerName(),
                    "attachment_name" => $attachment->Name,
                ]);
            }

            //Take a wound
            if ($id == 2)
            {
                $owner = $this->getOwningCard($game->theah);
                $woundEvent = EventFactory::createCharacterWoundedEvent($adversary->Id, $owner->Id, 1, $game->translate("Chose to take a wound from Disarm Maneuver."));
                $game->theah->eventCheck($woundEvent);
                $game->theah->queueEvent($woundEvent);

                $game->notifyAllPlayers("message", clienttranslate('<strong>Disarm:</strong> ${player_name} has chosen to take a wound.'), [
                    "player_name" => $game->getActivePlayerName(),
                ]);
            }

            $game->gamestate->nextState();
        }
    }
}