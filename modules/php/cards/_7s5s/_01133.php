<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01133 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Matushka's Efficiency";
        $this->Image = "img/cards/7s5s/133.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Usurra";

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            'Sorcery',
            'Dar Matushki',
        ];

        $this->Maneuvers = [
            new Maneuver_01133(),
        ];
    }

}