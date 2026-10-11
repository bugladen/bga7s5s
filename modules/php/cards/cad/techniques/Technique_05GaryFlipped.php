<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05GaryFlipped extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Destroy an attachment equipped to Rena: +1 Thrust. She heals a wound.";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null || $playerId != $owner->ControllerId)
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return count($this->getDestroyableAttachments($theah, $owner)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "05GaryFlipped",
                $this->Id
            );
            $event->theah->queueEvent($transitionEvent);
            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(
                "%s: Technique [%s] adds 1 Thrust.",
                $owner->getInjectCode(),
                $this->Name
            );
            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null && $owner->Id == $event->actorId)
            {
                $event->adversaryThreat += 1;
                $event->explanations[] = sprintf(
                    "%s: Technique [%s] adds 1 Threat.",
                    $owner->getInjectCode(),
                    $this->Name
                );
            }
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_05GARYFLIPPED)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $attachments = $owner !== null ? $this->getDestroyableAttachments($game->theah, $owner) : [];
            $args["attachments"] = array_map(
                fn($attachment) => ["id" => $attachment->Id, "name" => $attachment->Name],
                $attachments
            );
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_05GARYFLIPPED)
        {
            $owner = $this->getOwningCharacter($game->theah);
            if ($owner === null || ! in_array($id, $owner->Attachments))
            {
                throw new UserException("Attachment is not equipped to Rena");
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null || $attachment->FakeAttachment)
            {
                throw new UserException("Invalid attachment");
            }

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($attachment->ControllerId, $owner->Id, $attachment->Id);
            $game->theah->eventCheck($unequipEvent);
            $game->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent(
                $attachment,
                $owner->Id,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            // WHY: Printed "She heals a wound" after destroy. Skip when already at 0 wounds
            // to avoid a no-op heal event.
            if ($owner->Wounds > 0)
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent(
                    $owner->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $game->theah->queueEvent($healEvent);
            }

            $game->gamestate->nextState();
        }
    }

    /**
     * @return Attachment[]
     */
    private function getDestroyableAttachments(Theah $theah, $owner): array
    {
        $out = [];
        foreach ($owner->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment instanceof Attachment && ! $attachment->FakeAttachment)
            {
                $out[] = $attachment;
            }
        }
        return $out;
    }
}
