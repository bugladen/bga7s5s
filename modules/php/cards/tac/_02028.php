<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02028;

class _02028 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();
                
        $this->Name = clienttranslate('Battle of Wits');
        $this->Image = '02028.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 28;

        $this->initializeFaction('Montaigne');

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Challenge'),
            clienttranslate('Rescue'),
        ];

        $this->Text = clienttranslate("<p><b>Diplomat City Action:</b> Your performer issues a [Influence] challenge to target opposing character with 1[Influence] or more.</p><p><b>Maneuver:</b> +X[Thrust] where X is equal to your participant's [Influence]. If your participant is a <b>Diplomat</b>, gain Lethal.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02028(),
        ];

        $this->Maneuvers = [
            new Maneuver_02028(),
        ];
    }
}