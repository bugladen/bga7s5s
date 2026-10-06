<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01135 extends Maneuver
{
    private bool $IsActive;
    private bool $ReduceThrustNextRound;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+2 Parry, or Wound Adversary and Give -2 Thrust Next Round");
        $this->IsActive = false;
        $this->ReduceThrustNextRound = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        return $inDuel;
    }

    /**
     * WHY: Deferred -2 Thrust must survive Miyato/Ota (02043a) sending the Risk to The
     * Locker (and removing the maneuver clone at NewRound). Locker cards are deliberately
     * omitted from buildCity, so instance handleEvent never sees the adversary's
     * CalculateCombatCardStats. Global + EventHub apply mirrors Unravel (_04010).
     * Do NOT put locker cards into buildCity to "fix" this.
     *
     * @return list<array{adversaryId:int,amount:int,sourceInjectCode:string,maneuverId:string}>
     */
    public static function getPendingThrustReductions(Game $game): array
    {
        $pending = $game->globals->get(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS, []);
        return is_array($pending) ? $pending : [];
    }

    public static function armPendingThrustReduction(
        Game $game,
        int $adversaryId,
        int $amount,
        string $sourceInjectCode,
        string $maneuverId
    ): void {
        $pending = self::getPendingThrustReductions($game);
        $pending[] = [
            'adversaryId' => $adversaryId,
            'amount' => $amount,
            'sourceInjectCode' => $sourceInjectCode,
            'maneuverId' => $maneuverId,
        ];
        $game->globals->set(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS, $pending);
    }

    public static function applyPendingThrustReductions(EventDuelCalculateCombatCardStats $event): void
    {
        $pending = self::getPendingThrustReductions($event->theah->game);
        if ($pending === [])
        {
            return;
        }

        foreach ($pending as $entry)
        {
            if (($entry['adversaryId'] ?? 0) != $event->actorId)
            {
                continue;
            }

            $amount = (int) ($entry['amount'] ?? 0);
            if ($amount <= 0)
            {
                continue;
            }

            $source = $entry['sourceInjectCode'] ?? '';
            $event->explanations[] = sprintf(
                $event->theah->game->translate("%s reduces the Adversary's Thrust by %d."),
                $source,
                $amount
            );
            $event->removeThrust($amount);
        }
        // WHY not consume here: card text is "during their next round" — same as the old
        // IsActive window through that round's EndOfRound (multi combat-card edge).
    }

    /** Expire entries armed against the actor whose round just ended. */
    public static function expirePendingForActor(Game $game, int $actorId): void
    {
        $pending = self::getPendingThrustReductions($game);
        if ($pending === [])
        {
            return;
        }

        $remaining = [];
        foreach ($pending as $entry)
        {
            if (($entry['adversaryId'] ?? 0) == $actorId)
            {
                continue;
            }
            $remaining[] = $entry;
        }

        if ($remaining === [])
        {
            $game->globals->delete(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS);
        }
        else
        {
            $game->globals->set(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS, $remaining);
        }
    }

    public static function clearPendingForManeuver(Game $game, string $maneuverId): void
    {
        $pending = self::getPendingThrustReductions($game);
        if ($pending === [])
        {
            return;
        }

        $remaining = [];
        foreach ($pending as $entry)
        {
            if (($entry['maneuverId'] ?? '') === $maneuverId)
            {
                continue;
            }
            $remaining[] = $entry;
        }

        if ($remaining === [])
        {
            $game->globals->delete(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS);
        }
        else
        {
            $game->globals->set(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS, $remaining);
        }
    }

    public static function clearAllPending(Game $game): void
    {
        $game->globals->delete(Game::MIRELIS_REVISION_PENDING_THRUST_REDUCTIONS);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01135", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id && $this->IsActive && ! $this->ReduceThrustNextRound) 
        {
            $owner = $this->getOwningCard($event->theah);
            $event->parry += 2;
            $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Parry."), $owner->getInjectCode());
        }

        // WHY no EventDuelCalculateCombatCardStats here: apply via
        // applyPendingThrustReductions in EventHub so locker / clone-removed cases still hit.

        //Deactive at the end of the next players round
        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {            
            $owner = $this->getOwningCard($event->theah);
            $actor = $event->theah->getDuelRoundActor();
            if ($owner->ControllerId != $actor->ControllerId)
            {
                $this->IsActive = false;
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            self::clearPendingForManeuver($event->theah->game, $this->Id);
            $this->IsActive = false;
            $this->ReduceThrustNextRound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd)
        {
            self::clearAllPending($event->theah->game);
            $this->IsActive = false;
            $this->ReduceThrustNextRound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01135)
        {
            $owner = $this->getOwningCard($game->theah);
            if ($id == 1)
            {
                $this->ReduceThrustNextRound = false;
            }
            else if ($id == 2)
            {
                $adversary = $game->theah->getDuelRoundOpponent();
                $game->notify->all("message", clienttranslate('${card_inject_code} activated: ${character_name} is wounded and their Thrust is reduced by 2 next round.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                    "character_name" => $adversary->Name,
                ]);
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversary->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
                
                $this->ReduceThrustNextRound = true;
                self::armPendingThrustReduction(
                    $game,
                    $adversary->Id,
                    2,
                    $owner->getInjectCode(),
                    $this->Id
                );
            }

            $this->IsActive = true;
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState();
    }
}
