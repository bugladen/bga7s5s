<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03049 extends Technique
{
    // WHY: Choice (+1 vs engage Artifact for +2) happens in Resolve before Calculate.
    private int $ParryBonus = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("+1 Parry, or engage Artifact for +2 Parry");
        $this->ParryBonus = 0;
    }

    /**
     * @return Attachment[]
     */
    private function getUnengagedArtifacts(Theah $theah, Character $owner): array
    {
        $artifacts = [];
        foreach ($owner->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment !== null
                && ! $attachment->FakeAttachment
                && $attachment->hasTrait("Artifact")
                && ! $attachment->Engaged)
            {
                $artifacts[] = $attachment;
            }
        }
        return $artifacts;
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
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        // Base +1 Parry needs no Artifact — always legal when she is the actor.
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $artifacts = $this->getUnengagedArtifacts($event->theah, $owner);

            if (count($artifacts) == 0)
            {
                // No Artifact option — lock in the printed base bonus.
                $this->ParryBonus = 1;
                $owner->IsUpdated = true;
            }
            else
            {
                // WHY createTechniqueTransitionEvent: HIGHEST_PRIORITY so the choice
                // runs before EventDuelCalculateTechniqueValues in the same queue.
                $transition = EventFactory::createTechniqueTransitionEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    "03049",
                    $this->Id
                );
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($this->ParryBonus > 0)
            {
                $event->parry += $this->ParryBonus;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: Technique [%s] adds +%s Parry."),
                    $owner->getInjectCode(),
                    $this->Name,
                    $this->ParryBonus
                );
                $this->setUsed($event->theah, true);
            }
            $this->ParryBonus = 0;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->ParryBonus = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03049)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $artifacts = $this->getUnengagedArtifacts($game->theah, $owner);
            $args["attachments"] = array_map(
                fn($attachment) => ["id" => $attachment->Id, "name" => $attachment->Name],
                $artifacts
            );
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03049)
        {
            $owner = $this->getOwningCharacter($game->theah);

            if ($id == 0)
            {
                $this->ParryBonus = 1;
                $game->notify->all("message", clienttranslate('${card_inject_code}: Technique adds +1 Parry.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                ]);
            }
            else
            {
                if (! in_array($id, $owner->Attachments))
                {
                    throw new UserException($game->translate("Attachment is not equipped to Ekaterina."));
                }

                $attachment = $game->theah->getAttachmentById($id);
                if ($attachment === null
                    || $attachment->FakeAttachment
                    || ! $attachment->hasTrait("Artifact")
                    || $attachment->Engaged)
                {
                    throw new UserException($game->translate("Attachment must be an unengaged Artifact equipped to Ekaterina."));
                }

                $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);

                $this->ParryBonus = 2;
                $game->notify->all("message", clienttranslate('${card_inject_code}: Technique engages ${attachment_inject_code} for +2 Parry.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "attachment_inject_code" => $attachment->getInjectCode(),
                ]);
            }

            $owner->IsUpdated = true;
            $game->gamestate->nextState();
        }
    }
}
