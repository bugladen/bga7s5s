<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02061;

class _02061 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Unsanctioned Duel');
        $this->Image = '02061.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 61;
        $this->initializeFaction('Neutral');

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Challenge'),
            clienttranslate('Provocation'),
        ];

        $this->Text = clienttranslate("<p><b>Duelist City Action:</b> Engage your performer • Issue an unrefusable [Combat] challenge to target opposing non-<b>Leader</b>. At the start of the first round of the following duel, add a threat to both participants. These effects cannot be cancelled.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_02061(),
        ];
    }
}
