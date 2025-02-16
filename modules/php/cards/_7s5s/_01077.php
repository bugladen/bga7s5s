<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01077;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01077 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Broken-Time";
        $this->Image = "img/cards/7s5s/077.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Montaigne';
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            'Flourish',
            'Valroux',
        ];

        $this->Maneuvers = [
            new Maneuver_01077(),
        ];
    }
}