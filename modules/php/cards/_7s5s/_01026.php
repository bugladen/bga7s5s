<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01026 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('For the Family');
        $this->Image = '01026.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 26;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Glory'),
            clienttranslate('Zeal'),
        ];

        $this->Text = clienttranslate("<p><b>Red Hand City Action:</b> Destroy your performer • Engage target character at that location. If they are already engaged, send them Home instead.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01026(),
        ];
    }
}