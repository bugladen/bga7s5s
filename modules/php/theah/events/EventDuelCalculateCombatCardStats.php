<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelCalculateCombatCardStats extends Event
{
    public int $actorId;
    public int $adversaryId;
    public int $combatCardId;
    private(set) int $riposte;
    public bool $dashedRiposte;
    private(set) int $parry;
    public bool $dashedParry;
    private(set) int $thrust;
    public bool $dashedThrust;
    public bool $gambled;
    public bool $statsAddedToExistingCombatCard;
    public Array $explanations;

    public function __construct()
    {
        parent::__construct();
        $this->priority = Event::HIGH_PRIORITY;

        $this->actorId = 0;
        $this->adversaryId = 0;
        $this->combatCardId = 0;
        $this->riposte = 0;
        $this->dashedRiposte = false;
        $this->parry = 0;
        $this->dashedParry = false;
        $this->thrust = 0;
        $this->dashedThrust = false;
        $this->gambled = false;
        $this->statsAddedToExistingCombatCard = false;
        $this->explanations = [];
        $this->runEventHubAfterCards = true;
    }

    private function addDashedExplanation()
    {
        $this->explanations[] = sprintf($this->theah->game->translate("Value is dashed so will not be changed."));
    }

    public function addRiposte(int $value)
    {
        if (! $this->dashedRiposte)
        {
            $this->riposte += $value;
        }
        else
        {
            $this->addDashedExplanation();
        }
    }

    public function addParry(int $value)
    {
        if (! $this->dashedParry)
        {
            $this->parry += $value;
        }
        else
        {
            $this->addDashedExplanation();
        }
    }

    public function addThrust(int $value)
    {
        if (! $this->dashedThrust)
        {
            $this->thrust += $value;
        }
        else
        {
            $this->addDashedExplanation();
        }
    }

    // WHY allow negatives (no floor / no "only if > 0"): penalties like Syrneth Hand
    // removeParry(2) on a 1P combat card must store combat_parry = -1 so a later
    // Technique/Maneuver +1P nets to 0. Flooring here ate the overflow and left
    // combat_parry = 0; technique mode then summed 0 + 1 = 1P (bug: 4 threat → 1
    // remaining instead of 2). Effective R/P are floored at apply time in
    // DB::updateRoundWithCombatStats (all modes). EventHub already formats
    // negative combat contributions in the duel log.
    public function removeRiposte(int $value)
    {
        if (! $this->dashedRiposte)
        {
            $this->riposte -= $value;
        }
        else
        {
            $this->addDashedExplanation();
        }
    }

    public function removeParry(int $value)
    {
        if (! $this->dashedParry)
        {
            $this->parry -= $value;
        }
        else
        {
            $this->addDashedExplanation();
        }
    }

    public function removeThrust(int $value)
    {
        if (! $this->dashedThrust)
        {
            $this->thrust -= $value;
        }
        else
        {
            $this->addDashedExplanation();
        }
    }
}