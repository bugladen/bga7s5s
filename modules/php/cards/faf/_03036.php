<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03036 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Valroux Exemplar");
        $this->Image = '03036.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 36;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 1;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate("Valroux"),
            clienttranslate("Flourish"),
            clienttranslate("Demoralize")
        ];

        $this->Text = clienttranslate("<p>If your participant has more [Finesse] than the adversary, this card has -1 cost.</p>
        <p><b>Duelist Maneuver:</b> +1[Riposte] for each other card in your dueling line. If you have three or more other cards in your dueling line, the adversary discards a card.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03036(),
        ];
    }
}