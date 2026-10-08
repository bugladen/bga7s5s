<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05DabneyUS01 extends Technique
{
    public const CHOICE_RIPOSTE = 0;
    public const CHOICE_PARRY = 1;
    public const CHOICE_LETHAL = 2;

    // WHY: Public so IsUpdated on the owner persists the choice across Resolve → Calculate.
    public int $Choice = self::CHOICE_RIPOSTE;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "+1 Parry, +1 Riposte, or gain Lethal";
        $this->Choice = self::CHOICE_RIPOSTE;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return false;
        }

        // WHY: Parry / Riposte need EventDuelCalculateTechniqueValues; Lethal on challenge
        // was already a no-op (threat at stat cap / RH). With Thrust gone there is no
        // challenge-useful choice — duel-only like Technique_PlusOneParry.
        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner === null)
            {
                return;
            }

            $this->Choice = self::CHOICE_RIPOSTE;
            $owner->IsUpdated = true;

            // WHY createTechniqueTransitionEvent: HIGHEST_PRIORITY so choice completes before
            // EventDuelCalculateTechniqueValues reads Choice.
            $transition = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "05DabneyUS01",
                $this->Id
            );
            $event->theah->queueEvent($transition);

            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $inject = $owner !== null ? $owner->getInjectCode() : $this->Name;

            if ($this->Choice == self::CHOICE_PARRY)
            {
                $event->parry += 1;
                $event->explanations[] = sprintf(
                    "%s: Technique [%s] adds +1 Parry.",
                    $inject,
                    $this->Name
                );
            }
            else if ($this->Choice == self::CHOICE_RIPOSTE)
            {
                $event->riposte += 1;
                $event->explanations[] = sprintf(
                    "%s: Technique [%s] adds +1 Riposte.",
                    $inject,
                    $this->Name
                );
            }
            else if ($this->Choice == self::CHOICE_LETHAL)
            {
                $lethalEvent = EventFactory::createGainLethalEvent($event->actorId, $event->theah);
                $event->theah->queueEvent($lethalEvent);
                $event->explanations[] = sprintf(
                    "%s: Technique [%s] gains Lethal.",
                    $inject,
                    $this->Name
                );
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearChoice($event->theah);
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->clearChoice($event->theah);
        }
    }

    private function clearChoice(Theah $theah): void
    {
        $this->Choice = self::CHOICE_RIPOSTE;
        $owner = $this->getOwningCard($theah);
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        // WHY: Challenge picker state kept registered but technique is duel-only now
        // (Parry replaced Thrust — no challenge-useful choice). Still accept the state
        // id so a stale transition cannot soft-lock; reject non-duel choices below.
        if ($state == States::DUEL_CHOOSE_TECHNIQUE_05DABNEYUS01
            || $state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05DABNEYUS01)
        {
            $owner = $this->getOwningCharacter($game->theah);

            if ($id != self::CHOICE_RIPOSTE && $id != self::CHOICE_PARRY && $id != self::CHOICE_LETHAL)
            {
                throw new \Bga\GameFramework\UserException("Invalid Technique choice.");
            }

            if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05DABNEYUS01)
            {
                throw new \Bga\GameFramework\UserException("This Technique is only available during a duel.");
            }

            // WHY: Same rule as EventHub Technique Riposte strip — dashed combat Riposte
            // would zero the bonus; do not accept the choice.
            if ($id == self::CHOICE_RIPOSTE
                && $game->theah->currentRoundCombatCardsHaveDashedRiposte())
            {
                throw new \Bga\GameFramework\UserException("Riposte is not available — the combat card has dashed Riposte.");
            }

            // WHY: Mirror Riposte — EventHub zeroes Technique Parry when every combat card
            // this round is DashedParry.
            if ($id == self::CHOICE_PARRY
                && $game->theah->currentRoundCombatCardsHaveDashedParry())
            {
                throw new \Bga\GameFramework\UserException("Parry is not available — the combat card has dashed Parry.");
            }

            $this->Choice = $id;
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }

            if ($this->Choice == self::CHOICE_PARRY)
            {
                $game->notify->all("message", '${player_name} chooses +1 Parry for ${owner_inject_code}\'s Technique.', [
                    "player_name" => $game->getActivePlayerName(),
                    "owner_inject_code" => $owner !== null ? $owner->getInjectCode() : $this->Name,
                ]);
            }
            else if ($this->Choice == self::CHOICE_RIPOSTE)
            {
                $game->notify->all("message", '${player_name} chooses +1 Riposte for ${owner_inject_code}\'s Technique.', [
                    "player_name" => $game->getActivePlayerName(),
                    "owner_inject_code" => $owner !== null ? $owner->getInjectCode() : $this->Name,
                ]);
            }
            else
            {
                $game->notify->all("message", '${player_name} chooses Lethal for ${owner_inject_code}\'s Technique.', [
                    "player_name" => $game->getActivePlayerName(),
                    "owner_inject_code" => $owner !== null ? $owner->getInjectCode() : $this->Name,
                ]);
            }

            $game->gamestate->nextState();
        }
    }
}
