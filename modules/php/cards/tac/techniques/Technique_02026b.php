<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02026b extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Destroy target engaged attachment equipped to the adversary");
    }

    /**
     * WHY: Challenge has no duel round — do not call getDuelRoundOpponent() there
     * (null actor → fatal). Adversary is CHOSEN_TARGET via
     * stHighDramaChallengeActionResolveTechnique. Same rule as Technique_01193 / 04017.
     */
    private function getAdversary(Theah $theah): ?Character
    {
        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            return $theah->getDuelRoundOpponent();
        }

        $adversaryId = (int) $theah->game->globals->get(Game::CHOSEN_TARGET, 0);
        if (! $adversaryId)
        {
            return null;
        }

        return $theah->getCharacterById($adversaryId);
    }

    private function adversaryHasEngagedAttachment(Theah $theah): bool
    {
        $adversary = $this->getAdversary($theah);
        if ($adversary === null)
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

    private function queueAttachmentChooser(Theah $theah): void
    {
        if (! $this->adversaryHasEngagedAttachment($theah))
        {
            return;
        }

        $owner = $this->getOwningCard($theah);
        $transitionEvent = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "02026b", $this->Id);
        $theah->queueEvent($transitionEvent);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        // WHY: Card text is "Duelist Technique" — usable only when the equipped character has Duelist
        // (equip itself is not restricted; see journal 2026-03-30-02).
        $equipped = $this->getOwningCharacter($theah);
        if ($equipped == null || ! $equipped->hasTrait("Duelist"))
        {
            return false;
        }

        return $this->adversaryHasEngagedAttachment($theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            // WHY: Challenge Resolve runs before Accept/Refuse. Destroy must not fire on
            // Refuse — defer the chooser to EventGenerateChallengeThreat + CHALLENGE_ACCEPTED
            // (04017 shape). In-duel Resolve is the real effect timing.
            if ($event->inDuel)
            {
                $this->queueAttachmentChooser($event->theah);
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            // WHY: GENERATE_THREAT also runs on Refuse (to apply wound threat). Intervene
            // sets CHALLENGE_ACCEPTED without EventChallengeAccepted. Gate here so Refuse
            // never prompts; Accept/Intervene get the chooser from GENERATE_THREAT_EVENTS.
            if ($event->theah->game->globals->get(Game::CHALLENGE_ACCEPTED, false))
            {
                $this->queueAttachmentChooser($event->theah);
            }
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02026b
            || $state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_02026b)
        {
            $adversary = $this->getAdversary($game->theah);
            $attachments = [];
            if ($adversary !== null)
            {
                foreach ($adversary->Attachments as $attachmentId)
                {
                    $attachment = $game->theah->getAttachmentById($attachmentId);
                    if ($attachment && $attachment->Engaged)
                    {
                        $attachments[] = $attachment;
                    }
                }
            }
            $args["attachments"] = array_map(fn($attachment) => ["id" => $attachment->Id, "name" => $attachment->Name], $attachments);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02026b
            || $state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_02026b)
        {
            $adversary = $this->getAdversary($game->theah);
            if ($adversary === null)
            {
                throw new UserException($game->translate("Adversary not found"));
            }

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
            $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent($attachment, $owner->Id, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
        }
    }
}
