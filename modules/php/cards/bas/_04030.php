<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04030 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Tip the Scales");
        $this->Image = "04030.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 30;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Fortune")
        ];

        $this->Text = clienttranslate("<p>While your performer is a <b>Merchant</b> or <b>Scoundrel</b>, this card has -1 cost.</p>
<p><b>City Action:</b> Move your performer to an adjacent <b>City</b> location with more Renown.</p>
<p><b>Maneuver:</b> +1[Parry] or +1[Thrust]</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04030(),
        ];

        $this->Maneuvers = [
            new Maneuver_04030(),
        ];
    }
}
