<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Montoya;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05Montoya extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "The Aldana Ruse";
        $this->Image = "05Montoya.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 29;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            "Aldana",
            "Flourish",
        ];

        $this->Text = "<p>While you are not first player, this card has -1 Cost.</p>
<p><b>Duelist Maneuver:</b> +1 [Riposte] for each <b>Flourish</b> in your dueling line. Draw a card then put a card from your hand on top of your deck.</p>";

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_05Montoya(),
        ];
    }
}
