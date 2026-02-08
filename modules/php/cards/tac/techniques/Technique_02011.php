<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02011 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Engage Ranged Weapon: +1 Parry");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        $katain = $this->getOwningCharacter($theah);
        foreach ($katain->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment && $attachment->hasTrait("Ranged") && !$attachment->Engaged)
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02011", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry += 1;
            $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry."), $owner->getInjectCode(), $this->Name);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02011)
        {
            $katain = $this->getOwningCharacter($game->theah);
            $attachments = [];
            foreach ($katain->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment && $attachment->hasTrait("Ranged") && !$attachment->Engaged)
                {
                    $attachments[] = $attachment;
                }
            }
            $args["attachments"] = array_map(fn($attachment) => ["id" => $attachment->Id, "name" => $attachment->Name], $attachments);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02011)
        {
            $actor = $game->theah->getDuelRoundActor();

            if (! in_array($id, $actor->Attachments))
            {
                throw new \BgaUserException($game->translate("Attachment is not equipped to the Actor"));
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment && (! $attachment->hasTrait("Ranged") || $attachment->Engaged))
            {
                throw new \BgaUserException($game->translate("Attachment must have Ranged Trait and be not engaged"));
            }

            $engageEvent = EventFactory::createCardEngagedEvent($actor->ControllerId, $id, $actor->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $game->gamestate->nextState();
        }
    }
}