<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01115;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01115 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Taunt";
        $this->Image = "img/cards/7s5s/115.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Castille";
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            'Flourish',
            'Demoralize',
            'Torres',
        ];

        $this->Maneuvers = [
            new Maneuver_01115(),
        ];
    }
}