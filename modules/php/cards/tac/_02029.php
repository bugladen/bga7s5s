<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers\Maneuver_02029;

class _02029 extends Risk implements IHasActions, IHasManeuvers
{
    use ActionTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();
        
        
        $this->Name = clienttranslate('Diplomatic Impunity');
        $this->Image = '02029.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 29;

        $this->initializeFaction('Montaigne');

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 0;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Bureaucracy'),
            clienttranslate('Camaraderie'),
        ];

        $this->Text = clienttranslate("<p><b>Diplomat Action:</b> If you control more <b>Diplomats</b> at your performer's location than the amount of Renown there • Claim that location.</p><p><b>Maneuver</b> +1[riposte] for each <b>Diplomat</b> you control at this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02029(),
        ];

        $this->Maneuvers = [
            new Maneuver_02029(),
        ];
    }
}