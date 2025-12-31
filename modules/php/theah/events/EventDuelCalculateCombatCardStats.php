<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah\events;

class EventDuelCalculateCombatCardStats extends Event
{
    public int $actorId;
    public int $adversaryId;
    public int $combatCardId;
    public int $riposte;
    public bool $dashedRiposte;
    public int $parry;
    public bool $dashedParry;
    public int $thrust;
    public bool $dashedThrust;
    public bool $gambled;
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

    public function removeRiposte(int $value)
    {
        if (! $this->dashedRiposte)
        {
            if ($this->riposte > 0)
            {
                $this->riposte -= $value;
            }
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
            if ($this->parry > 0)
            {
                $this->parry -= $value;
            }
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
            if ($this->thrust > 0)
            {
                $this->thrust -= $value;
            }
        }
        else
        {
            $this->addDashedExplanation();
        }
    }
}