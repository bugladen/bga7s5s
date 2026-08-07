<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
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

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $attachment = $this->getOwningCard($theah);
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
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

            // WHY: Academic/Hunter is a Resolve-time effect gate, not an availability gate —
            // printed "If …" so +1 Thrust/engage still works for other hosts.
            if ($owner->hasTrait("Academic") || $owner->hasTrait("Hunter"))
            {
                $adversary = $event->theah->getDuelRoundOpponent();
                if ($adversary === null)
                {
                    return;
                }

                $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
                if (count($hand) > 0)
                {
                    // WHY: sourceId = attachment — FrameworkActionsTrait hydrates source and
                    // getTechniqueById; character sourceId would hide an attachment-hosted technique.
                    $transition = EventFactory::createTechniqueTransitionEvent(
                        $adversary->ControllerId,
                        $attachment->Id,
                        "04017",
                        $this->Id
                    );
                    $event->theah->queueEvent($transition);
                }
                else
                {
                    $event->theah->game->notify->all("message", clienttranslate('${technique_inject_code}: ${player_name} has no cards to discard.'), [
                        "technique_inject_code" => $attachment->getInjectCode(),
                        "player_name" => $event->theah->game->getPlayerNameById($adversary->ControllerId),
                    ]);
                }
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

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_04017)
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
