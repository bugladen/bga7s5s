<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01051 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Answering the Call";
        $this->Image = "img/cards/7s5s/051.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Eisen';
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 3;

        $this->Traits = [
            'Flourish',
            'Camaraderie',
            'Duty',
        ];

        $this->Maneuvers = [
            new Maneuver_01051(),
        ];
    }
}