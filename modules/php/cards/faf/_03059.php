<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03059 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Insightful");
        $this->Image = '03059.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 59;

        $this->initializeFaction('Ussura');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Virtue'),
            clienttranslate('Flourish')
        ];

        $this->Text = clienttranslate("<p><b>Maneuver:</b> Look at the top three cards of your adversary's deck. Reveal one and add its [Parry] or [Thrust] to this card. Replace them in any order. If your participant is an <b>Academic</b>, sink any of those cards instead of replacing them.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03059(),
        ];
    }
}
