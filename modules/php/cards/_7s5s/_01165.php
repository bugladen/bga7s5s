<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01165;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01165 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "I Know that Trick!";
        $this->Image = "img/cards/7s5s/165.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Prepared',
        ];

        $this->Maneuvers = [
            new Maneuver_01165(),
        ];
    }
}