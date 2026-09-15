<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04040 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Solvente Universal');
        $this->Image = '04040.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 40;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Alquimia"),
            clienttranslate("Acid"),
            clienttranslate("Chymystry")
        ];

        $this->Text = clienttranslate("<p><b>Academic City Action:</b> Target an opposing equipped character • Send an attachment equipped to them to <b>The Locker</b>. Wound that character.</p>
<p><b>Academic Maneuver:</b> Wound the adversary.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04040(),
        ];

        $this->Maneuvers = [
            new Maneuver_04040(),
        ];
    }
}
