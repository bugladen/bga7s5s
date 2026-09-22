<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04060 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Worldly');
        $this->Image = '04060.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 60;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate('Discovery'),
            clienttranslate('Flourish')
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Choose a controlled <b>City</b> location with no Renown or no characters • It becomes uncontrolled.</p>
<p><b>Maneuver:</b> +1[Thrust]. If this location is uncontrolled, +1[Riposte] instead.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04060(),
        ];

        $this->Maneuvers = [
            new Maneuver_04060(),
        ];
    }
}
