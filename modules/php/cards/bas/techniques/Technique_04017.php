<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_04017 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Engage: +1 Thrust; Academic/Hunter adversary discards");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $attachment = $this->getOwningCard($theah);
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return false;
        }

        // WHY: Challenge activation has no duel round actor — only gate actor==owner
        // when IN_DUEL. Outside duel (challenge TechniqueAvailable), performer is
        // already the host via getAvailableCharacterTechniques.
        if ($theah->game->globals->get(Game::IN_DUEL, false))
        {
            $actor = $theah->getDuelRoundActor();
            if ($actor === null || $actor->Id !== $owner->Id)
            {
                return false;
            }
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $owner = $this->getOwningCharacter($event->theah);
            if ($attachment === null || $owner === null)
            {
                return;
            }

            $engageEvent = EventFactory::createCardEngagedEvent(
                $event->playerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $event->theah->queueEvent($engageEvent);

            // WHY: Challenge discard waits for Accept/Intervene (EventGenerateChallengeThreat
            // + CHALLENGE_ACCEPTED). Refuse still runs Resolve and GenerateThreat — queuing
            // discard here would force it even when the challenge is rejected.
            if ($event->inDuel)
            {
                $participant = $event->theah->getCharacterById($event->actorId) ?? $owner;
                $this->queueAdversaryDiscardIfEligible(
                    $event->theah,
                    $participant,
                    $attachment,
                    $event->adversaryId
                );
            }
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            // WHY: Challenge has no EventDuelCalculateTechniqueValues — +1 Thrust becomes
            // +1 adversary threat here (same as Technique_PlusOneThrust). Applies on Accept
            // and Reject (refuse wounds); discard below is separately gated.
            $ownerChar = $this->getOwningCharacter($event->theah);
            if ($ownerChar === null || $ownerChar->Id == $event->actorId)
            {
                $attachment = $this->getOwningCard($event->theah);
                $event->adversaryThreat += 1;
                $event->explanations[] = sprintf(
                    $event->theah->game->translate("%s: Technique [%s] adds 1 Threat."),
                    $attachment !== null ? $attachment->getInjectCode() : $this->Name,
                    $this->Name
                );
            }

            // WHY: GENERATE_THREAT runs after Accept, Intervene, and Reject. Intervene does
            // not fire EventChallengeAccepted but sets CHALLENGE_ACCEPTED. Gate discard on
            // that flag so Refuse never prompts. AdversaryId is CHOSEN_TARGET post-Intervene.
            if ($event->theah->game->globals->get(Game::CHALLENGE_ACCEPTED, false))
            {
                $attachment = $this->getOwningCard($event->theah);
                $owner = $this->getOwningCharacter($event->theah);
                if ($attachment === null || $owner === null)
                {
                    return;
                }

                $participant = $event->theah->getCharacterById($event->actorId) ?? $owner;
                $this->queueAdversaryDiscardIfEligible(
                    $event->theah,
                    $participant,
                    $attachment,
                    $event->adversaryId
                );
            }
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $event->thrust += 1;
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s: Technique [%s] adds 1 Thrust."),
                $attachment !== null ? $attachment->getInjectCode() : $this->Name,
                $this->Name
            );
        }
    }

    /**
     * Academic/Hunter Resolve-time "If" gate — not availability.
     * Queues adversary hand discard picker when hand is non-empty.
     */
    private function queueAdversaryDiscardIfEligible(
        Theah $theah,
        $participant,
        $attachment,
        int $adversaryId
    ): void
    {
        if ($participant === null
            || (! $participant->hasTrait("Academic") && ! $participant->hasTrait("Hunter")))
        {
            return;
        }

        if (! $adversaryId)
        {
            $adversaryId = (int) $theah->game->globals->get(Game::CHOSEN_TARGET, 0);
        }
        $adversary = $theah->getCharacterById($adversaryId);
        if ($adversary === null && $adversaryId)
        {
            $adversary = $theah->game->getChallengeLastKnownCharacter($adversaryId);
        }
        if ($adversary === null)
        {
            return;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
        if (count($hand) > 0)
        {
            // WHY: sourceId = attachment — FrameworkActionsTrait hydrates source and
            // getTechniqueById; character sourceId would hide an attachment-hosted technique.
            // Challenge path transitions from GENERATE_THREAT_EVENTS (Accept/Intervene hub).
            $transition = EventFactory::createTechniqueTransitionEvent(
                $adversary->ControllerId,
                $attachment->Id,
                "04017",
                $this->Id
            );
            $theah->queueEvent($transition);
        }
        else
        {
            $theah->game->notify->all("message", clienttranslate('${technique_inject_code}: ${player_name} has no cards to discard.'), [
                "technique_inject_code" => $attachment->getInjectCode(),
                "player_name" => $theah->game->getPlayerNameById($adversary->ControllerId),
            ]);
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04017
            || $state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04017)
        {
            $card = $game->getCardObjectFromDb($id);

            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $playerId = $game->getActivePlayerId();

            if ($card->ControllerId != $playerId)
            {
                throw new \BgaUserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Card not in your hand"));
            }

            $attachment = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                $card->OwnerId,
                $card->Id,
                $attachment !== null ? $attachment->Id : 0,
                $asPayment = false,
                $asPlayed = false,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
        }
    }
}
