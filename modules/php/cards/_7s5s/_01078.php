<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01078;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAmARiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01078 extends Risk implements IHasActions, IAmARiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Defending Honor");
        $this->Image = "img/cards/7s5s/078.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Montaigne');
        
        $this->WealthCost = 1;
        $this->Riposte = 1;
        $this->Parry = 0;
        $this->Thrust = 3;

        $this->Traits = [
            'Challenge',
            'Provocation',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01078(),
        ];
    }
}