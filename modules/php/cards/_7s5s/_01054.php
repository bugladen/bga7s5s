<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01054 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Iron Reply");
        $this->Image = "01054.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Eisen');

        $this->WealthCost = 1;
        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            'Flourish',
            'Eisenfaust',
        ];

        $this->Text = clienttranslate("<p>While your participant is equipped with an Eisenfaust attachment, this card has -1 cost.</p><p>Maneuver: If your participant has equal or greater [Com] than the adversary, or is equipped with an Eisenfaust attachment • Wound the adversary.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01054(),
        ];
    }
}