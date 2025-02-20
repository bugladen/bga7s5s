<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01108;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01108 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Life in the Canals";
        $this->Image = "img/cards/7s5s/108.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Castille";
        
        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            'Flourish',
            'Cunning',
            'El Punal Occulto',
        ];

        $this->Maneuvers = [
            new Maneuver_01108(),
        ];
    }
}