<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01079;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01079 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Disarm");
        $this->Image = "01079.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Montaigne");
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Demoralize',
        ];

        $this->Text = clienttranslate("<p>While your participant has more [Finesse] than the adversary, this card has -1 cost.</p><p>Maneuver: Target the adversary's Weapon • They may destroy it. If they do not, wound them.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01079(),
        ];
    }
}