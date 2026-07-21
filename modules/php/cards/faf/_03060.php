<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03060 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public bool $WillEngage = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Matushka's Song");
        $this->Image = '03060.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 60;

        $this->initializeFaction('Ussura');

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate('Sorcery'),
            clienttranslate('Dar Matushki'),
            clienttranslate('Flourish')
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer City Action:</b> You may engage your performer, if you do, ignore all costs • Heal two wounds from another character at this location.</p>
<p><b>Gambling Maneuver:</b> If you control a <b>Sorcerer</b> at this location • Heal two wounds from your participant.</p>");

        $this->WillEngage = false;

        $this->resetCard();

        $this->Actions = [
            new Action_03060(),
        ];

        $this->Maneuvers = [
            new Maneuver_03060(),
        ];
    }
}
