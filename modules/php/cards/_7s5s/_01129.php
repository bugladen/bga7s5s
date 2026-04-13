<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers\Maneuver_01129;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;

class _01129 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Borets");
        $this->Image = "01129.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 129;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Porte'),
        ];

        $this->Text = clienttranslate("<p><b>Maneuver:</b> For the rest of the duel, other Maneuvers and Techniques cannot be used.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_01129(),
        ];
    }
}