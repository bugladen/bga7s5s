<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

trait FactionCardTrait
{
    public int $Riposte = 0;
    public int $Parry = 0;
    public int $Thrust = 0;

    function addFactionProperties(&$properties)
    {
        //Add faction deck card specific properties
        $properties['riposte'] = $this->Riposte;
        $properties['parry'] = $this->Parry;
        $properties['thrust'] = $this->Thrust;

        $properties['deckOrigin'] = 'Faction';
    }
}