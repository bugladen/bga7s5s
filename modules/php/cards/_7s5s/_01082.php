<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01082;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01082 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("A Heroic End");
        $this->Image = "01082.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Montaigne");
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Heroic'),
            clienttranslate('Final Strike'),
        ];

        $this->Text = clienttranslate("<p>Maneuver: Final Strike • Add two threat to the adversary and gain Lethal. (Final Strike activates if your participant is destroyed the round this card is played. Lethal ignores Restricted Hostilities.)</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01082(),
        ];
    }
}