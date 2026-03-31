<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02026b extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Destroy target engaged attachment equipped to the adversary");
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

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary == null)
        {
            return false;
        }

        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment && $attachment->Engaged)
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
            $transitionEvent = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "02026b", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02026b)
        {
            $adversary = $game->theah->getDuelRoundOpponent();
            $attachments = [];
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment && $attachment->Engaged)
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

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02026b)
        {
            $adversary = $game->theah->getDuelRoundOpponent();

            if (! in_array($id, $adversary->Attachments))
            {
                throw new UserException($game->translate("Attachment is not equipped to the adversary"));
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null || ! $attachment->Engaged)
            {
                throw new UserException($game->translate("Attachment must be engaged"));
            }

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($attachment->ControllerId, $attachment->AttachedToId, $attachment->Id);
            $game->theah->eventCheck($unequipEvent);
            $game->theah->queueEvent($unequipEvent);

            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location, $owner->Id, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
        }
    }
}
