<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver_01031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01031 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Rough 'em Up";
        $this->Image = 'img/cards/7s5s/031.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 31;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            'Flourish',
            'Brawl',
            'Gang',
            'Zeal',
        ];

        $this->Maneuvers = [
            new Maneuver_01031(),
        ];

    }
}