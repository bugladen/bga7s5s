<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

trait FactionCardTrait
{
    public int $Riposte = 0;
    public int $Parry = 0;
    public int $Thrust = 0;

    public bool $DashedRiposte = false;
    public bool $DashedParry = false;
    public bool $DashedThrust = false;

    function addFactionProperties(&$properties)
    {
        //Add faction deck card specific properties
        $properties['riposte'] = $this->Riposte;
        $properties['dashedRiposte'] = $this->DashedRiposte;
        $properties['parry'] = $this->Parry;
        $properties['dashedParry'] = $this->DashedParry;
        $properties['thrust'] = $this->Thrust;
        $properties['dashedThrust'] = $this->DashedThrust;

        $properties['deckOrigin'] = 'Faction';
    }

    public function getRiposte(): int
    {
        return $this->Riposte;
    }
}