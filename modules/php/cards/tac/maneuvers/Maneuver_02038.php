<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02038 extends Maneuver
{
    private string $RequiredTrait;
    private string $StatType;

    public function __construct(string $requiredTrait, string $statType, string $name)
    {
        parent::__construct();

        $this->RequiredTrait = $requiredTrait;
        $this->StatType = $statType;
        $this->Name = $name;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if (! $actor->hasTrait($this->RequiredTrait))
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();

        $actorStat = match($this->StatType) {
            'Combat' => $actor->ModifiedCombat,
            'Finesse' => $actor->ModifiedFinesse,
        };
        $adversaryStat = match($this->StatType) {
            'Combat' => $adversary->ModifiedCombat,
            'Finesse' => $adversary->ModifiedFinesse,
        };

        return $actorStat > $adversaryStat;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $this->liveAdversary($event->theah);

            // WHY: "unless the adversary suffers a wound" needs a living character.
            // getDuelRoundOpponent() restores the last in-play snapshot, so a character
            // already in the Locker still looked able to take the wound and cancel the draw.
            if (! $this->adversaryCanSufferWound($adversary, $event->theah))
            {
                $this->drawBecauseAdversaryCannotSufferWound($event->theah->game, $owner, $adversary);
                return;
            }

            $transitionEvent = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "02038", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_02038)
        {
            $adversary = $this->liveAdversary($game->theah);
            $args["canTakeWound"] = $this->adversaryCanSufferWound($adversary, $game->theah);
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_02038)
        {
            $owner = $this->getOwningCard($game->theah);
            $adversary = $this->liveAdversary($game->theah);

            if ($id == 1)
            {
                // WHY: Same live-character check as resolve. A stale "Suffer a Wound"
                // must not apply a wound to a character already in Discard or Locker.
                if (! $this->adversaryCanSufferWound($adversary, $game->theah))
                {
                    $this->drawBecauseAdversaryCannotSufferWound($game, $owner, $adversary);
                }
                else
                {
                    $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                    $game->theah->queueEvent($woundEvent);

                    $game->notify->all("message", clienttranslate('${player_name} has chosen to suffer a wound.'), [
                        "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                    ]);
                }
            }

            if ($id == 2)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);

                if ($adversary !== null)
                {
                    $game->notify->all("message", clienttranslate('${player_name} has chosen to let their opponent draw a card.'), [
                        "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                    ]);
                }
            }
        }

        $game->gamestate->nextState();
    }

    /**
     * WHY: Live row, not getDuelRoundOpponent(). The round-opponent helper puts back
     * the last in-play copy (city location, old wounds) after the character is destroyed.
     */
    private function liveAdversary(Theah $theah): ?Character
    {
        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return null;
        }

        return $theah->getCharacterById($theah->getDuelOpponentId($actor->Id));
    }

    private function adversaryCanSufferWound(?Character $adversary, Theah $theah): bool
    {
        return $adversary !== null && ! $theah->game->characterIsInDiscardOrLocker($adversary);
    }

    private function drawBecauseAdversaryCannotSufferWound(Game $game, Card $owner, ?Character $adversary): void
    {
        $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
        $game->theah->queueEvent($drawEvent);

        if ($adversary === null)
        {
            return;
        }

        $game->notify->all("message", clienttranslate('${card_inject_code}: ${character_inject_code} has been destroyed and cannot suffer a wound, so ${player_name} draws a card.'), [
            "card_inject_code" => $owner->getInjectCode(),
            "character_inject_code" => $adversary->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
        ]);
    }
}
