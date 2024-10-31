<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class FactionDeckCard extends Card
{
    public int $WealthCost;
    public int $Riposte;
    public int $Parry;
    public int $Thrust;

    public function __construct()
    {
        parent::__construct();

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 0;
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add faction deck card specific properties
        $properties['wealthCost'] = $this->WealthCost;
        $properties['riposte'] = $this->Riposte;
        $properties['parry'] = $this->Parry;
        $properties['thrust'] = $this->Thrust;

        $properties['deckOrigin'] = 'Faction';

        return $properties;
    }

}