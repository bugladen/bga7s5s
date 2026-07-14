<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03047a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03047b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03047 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Proper Drama");
        $this->Image = '03047.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 47;

        $this->initializeFaction("Castille");

        $this->WealthCost = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 0;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Savvy"),
            clienttranslate("El Punal Occulto"),
        ];

        $this->Text = clienttranslate("<p><b>Scoundrel Maneuver:</b> +1[Riposte]. If the adversary gambles during their next round, you choose their combat card.</p>
        <p><b>Duelist Maneuver:</b> The adversary cannot gamble during their next round.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03047a(),
            new Maneuver_03047b(),
        ];
    }
}
