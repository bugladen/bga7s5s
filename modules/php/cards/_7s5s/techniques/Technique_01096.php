<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01096 extends Technique
{
    private bool $AdversaryWoundedThisRound = false;

    /** Captured at Resolve — challenge has no duel opponent yet. */
    public int $AdversaryId = 0;

    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Steal Adversary's Equipped Card at End of Round");
        $this->IsActive = false;
        $this->AdversaryWoundedThisRound = false;
        $this->AdversaryId = 0;
    }

    /**
     * WHY: Challenge has no duel round — do not call getDuelRoundOpponent() there
     * (null actor → fatal). Adversary is CHOSEN_TARGET via
     * stHighDramaChallengeActionResolveTechnique. Same rule as Technique_02026b.
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

    /**
     * Attachment is legal to steal onto Ratón (Maneuver_01113 equip gate, no wealth).
     * WHY: Effect equips to Ratón — hide the Technique / chooser options when every
     * adversary attachment fails canAttachTo / hasEquipRestrictions (e.g. Duelist-only).
     */
    private function attachmentIsLegalForRaton(Theah $theah, Character $raton, Attachment $attachment): bool
    {
        [$hasRestrictions] = $theah->game->hasEquipRestrictions($raton, $attachment);
        if ($hasRestrictions || ! $attachment->canAttachTo($raton))
        {
            return false;
        }

        return true;
    }

    /** @return Attachment[] */
    private function getStealableAttachmentsOnCharacter(Theah $theah, Character $raton, Character $adversary): array
    {
        $stealable = [];
        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment === null)
            {
                continue;
            }
            if ($this->attachmentIsLegalForRaton($theah, $raton, $attachment))
            {
                $stealable[] = $attachment;
            }
        }

        return $stealable;
    }

    private function adversaryHasStealableAttachment(Theah $theah): bool
    {
        $raton = $this->getOwningCharacter($theah);
        $adversary = $this->getAdversary($theah);
        if ($raton === null || $adversary === null)
        {
            return false;
        }

        return count($this->getStealableAttachmentsOnCharacter($theah, $raton, $adversary)) > 0;
    }

    private function clearDeferredState(Theah $theah): void
    {
        $this->IsActive = false;
        $this->AdversaryWoundedThisRound = false;
        $this->AdversaryId = 0;
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        return $this->adversaryHasStealableAttachment($theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Arm: steal at end of adversary's next round if they were not wounded during it.
        // WHY: Use $event->adversaryId (CHOSEN_TARGET on challenge Resolve) — do not
        // call getDuelOpponentId / getDuelRoundOpponent; challenge has no duel yet.
        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $adversaryId = $event->adversaryId;
            if (! $adversaryId)
            {
                $adversaryId = (int) $event->theah->game->globals->get(Game::CHOSEN_TARGET, 0);
            }

            $this->IsActive = true;
            $this->AdversaryWoundedThisRound = false;
            $this->AdversaryId = $adversaryId;
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventCharacterWounded && $this->IsActive)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->characterId == $this->AdversaryId)
            {
                $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);
                if ($inDuel && $event->wounds > 0)
                {
                    $this->AdversaryWoundedThisRound = true;
                    if ($owner !== null)
                    {
                        $owner->IsUpdated = true;
                    }
                }
            }
        }

        if ($event instanceof EventDuelEndOfRound && $this->IsActive && $this->AdversaryId != 0)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($this->AdversaryId == $event->actorId)
            {
                // Adversary's next round ended — EndOfRound keys on stored AdversaryId so
                // challenge-armed activations skip the challenger's round-1 EndOfRound.
                if (! $this->AdversaryWoundedThisRound)
                {
                    $ownerCard = $this->getOwningCard($event->theah);
                    $adversary = $event->theah->getCharacterById($this->AdversaryId);
                    $raton = $this->getOwningCharacter($event->theah);
                    if ($adversary !== null && $raton !== null)
                    {
                        $stealable = $this->getStealableAttachmentsOnCharacter($event->theah, $raton, $adversary);
                        if (count($stealable) > 0)
                        {
                            $game = $event->theah->game;
                            $game->notify->all("message", clienttranslate('${owner_inject_code}: Technique has activated. ${player_name} will steal an attachment from ${opponent_name}.'), [
                                "owner_inject_code" => $ownerCard->getInjectCode(),
                                "player_name" => $game->getPlayerNameById($ownerCard->ControllerId),
                                "opponent_name" => $game->getPlayerNameById($adversary->ControllerId),
                            ]);
                            $transition = EventFactory::createTransitionEvent($ownerCard->ControllerId, $ownerCard->Id, "01096", $this->Id);
                            $event->theah->queueEvent($transition);
                        }
                    }
                }

                $this->clearDeferredState($event->theah);
            }
            else
            {
                // WHY: Reset wound tracking at the end of non-adversary rounds so that
                // wounds dealt during Ratón's round don't carry over. The card only cares
                // about wounds "during" the adversary's round, not earlier rounds.
                // After Challenge this also clears challenger round-1 wounds before the
                // adversary's first round begins.
                $this->AdversaryWoundedThisRound = false;
                if ($owner !== null)
                {
                    $owner->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearDeferredState($event->theah);
        }

        // WHY: Resolve runs before Accept/Reject. Refuse never starts a duel, so
        // clear here or the flag would leak into a later unrelated duel.
        // Card text: "(There are no adversaries if the challenge is refused.)"
        if ($event instanceof EventChallengeRejected && $this->IsActive)
        {
            $character = $this->getOwningCharacter($event->theah);
            if ($character !== null && $character->Id == $event->challengerId)
            {
                $this->clearDeferredState($event->theah);
            }
        }

        if ($event instanceof EventDuelEnd && $this->IsActive)
        {
            $this->clearDeferredState($event->theah);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_END_OF_ROUND_01096)
        {
            // WHY: AdversaryId is cleared when EndOfRound queues this transition; the
            // duel round actor is still the character whose round just ended.
            $adversary = $game->theah->getDuelRoundActor();
            $raton = $this->getOwningCharacter($game->theah);
            $attachments = [];
            if ($adversary !== null && $raton !== null)
            {
                foreach ($this->getStealableAttachmentsOnCharacter($game->theah, $raton, $adversary) as $attachment)
                {
                    $attachments[] = [
                        "id" => $attachment->Id,
                        "name" => $game->translate($attachment->Name),
                    ];
                }
            }
            $args['attachments'] = $attachments;
        }
        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_END_OF_ROUND_01096)
        {
            $attachment = $game->theah->getAttachmentById($id);

            if ($attachment == null)
            {
                throw new \BgaUserException($game->translate("Invalid attachment ID."));
            }

            $adversary = $game->theah->getDuelRoundActor();
            if ($adversary === null || ! in_array($attachment->Id, $adversary->Attachments))
            {
                throw new \BgaUserException($game->translate("Attachment is not equipped to Adversary."));
            }

            $owner = $this->getOwningCard($game->theah);
            $raton = $this->getOwningCharacter($game->theah);
            if ($raton === null || ! $this->attachmentIsLegalForRaton($game->theah, $raton, $attachment))
            {
                throw new \BgaUserException($game->translate("Attachment cannot be equipped to Ratón."));
            }

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($adversary->ControllerId, $adversary->Id, $attachment->Id);
            $game->theah->queueEvent($unequipEvent);

            //Some attachments actually attach to different targets
            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $owner->Id);

            $equipEvent = EventFactory::createAttachmentEquippedEvent($owner->ControllerId, $actualTargetId, $attachment->Id, 0, 0, $asAction = true, $explanations = '', false, $owner->Id, $this->Id);
            $game->theah->queueEvent($equipEvent);

            $this->setUsed($game->theah, true);

            $game->gamestate->nextState();
        }
    }


}
