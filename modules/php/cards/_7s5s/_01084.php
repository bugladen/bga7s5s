<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01084;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01084 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Master of Valroux Style");
        $this->Image = "01084.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 84;
        $this->initializeFaction("Montaigne");
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Valroux'),
        ];

        $this->Text = clienttranslate("<p>While the adversary is engaged, this card has -1 cost.</p><p><b>Duelist Maneuver:</b> +1 [Riposte] and draw a card. During the adversary's next round, their combat card gains +1 [Thrust.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01084(),
        ];
    }
}