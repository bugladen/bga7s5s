<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01108a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01108b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01108 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Life in the Canals");
        $this->Image = "01108.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Castille");
        
        $this->WealthCost = 0;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Cunning'),
            clienttranslate('El Punal Occulto'),
        ];

        $this->Text = clienttranslate("<p>Scoundrel Maneuver: The adversary discards a card.</p><p>Pirate Maneuver: Draw a card.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01108a(),
            new Maneuver_01108b(),
        ];
    }
}