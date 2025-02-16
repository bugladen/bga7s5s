<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01084;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01084 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Master of the Valroux Style";
        $this->Image = "img/cards/7s5s/084.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Montaigne";
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Valroux',
        ];

        $this->Maneuvers = [
            new Maneuver_01084(),
        ];
    }
}