<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01107 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "It's Personal";
        $this->Image = "img/cards/7s5s/107.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Castille";
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Aldana',
        ];

        $this->Maneuvers = [
            new Maneuver_01107(),
        ];
    }
}