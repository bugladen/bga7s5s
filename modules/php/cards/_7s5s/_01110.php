<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01110;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01110 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Nothing Personal");
        $this->Image = "01110.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 110;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Aldana'),
        ];

        $this->Text = clienttranslate("<p><b>Maneuver:</b> Wound the adversary. If your participant has 3 [Combat] or more, this location becomes uncontrolled unless they take another wound.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01110(),
        ];
    }
}