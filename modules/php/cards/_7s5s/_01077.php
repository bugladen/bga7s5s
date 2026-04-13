<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01077;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01077 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Broken-Time");
        $this->Image = "01077.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 77;
        $this->initializeFaction('Montaigne');
        
        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Valroux'),
        ];

        $this->Text = clienttranslate("<p><b>Duelist Maneuver:</b> Reveal cards from your deck equal to your participant's [Finesse]. Play one as an additional combat card and sink the rest. You may use a Maneuver on it, paying all costs.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01077(),
        ];
    }
}