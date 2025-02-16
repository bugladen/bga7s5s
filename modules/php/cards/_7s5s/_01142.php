<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01142;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01142 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Sunder";
        $this->Image = "img/cards/7s5s/142.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 2;

        $this->Traits = [
            'Flouish',
            'Demoralize',
        ];

        $this->Maneuvers = [
            new Maneuver_01142(),
        ];
    }

}