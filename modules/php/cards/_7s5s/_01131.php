<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01131;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01131 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Iron and Velvet";
        $this->Image = "img/cards/7s5s/131.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            'Challenge',
            'Kulachniy Boi',
        ];

        $this->Maneuvers = [
            new Maneuver_01131(),
        ];
    }

}