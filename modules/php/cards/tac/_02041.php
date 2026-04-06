<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02041;

class _02041 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate('You Cheated!');
        $this->Image = '02041.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 41;

        $this->initializeFaction('Castille');

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Cheating'),
            clienttranslate('Savvy'),
        ];

        $this->Text = clienttranslate("<b>Scoundrel Maneuver:</b> This duel becomes a duel of [Finesse] for the remainder of the duel.<br><i>(Use [Finesse] for Restricted Hostilities, including all current threat.)</i>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_02041(),
        ];
    }
}