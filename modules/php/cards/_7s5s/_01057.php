<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01057 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Precision";
        $this->Image = "img/cards/7s5s/057.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Eisen';
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            'Flourish',
            'Ranged',
        ];

        $this->Maneuvers = [
            new Maneuver_01057(),
        ];
    }
}