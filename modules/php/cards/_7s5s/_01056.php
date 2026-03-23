<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01056 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Along");
        $this->Image = "01056.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Eisen');
        
        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 1;
        $this->Thrust = 4;

        $this->Traits = [
            'Challenge',
            'Provocation',
        ];

        $this->Text = "<p>City Action: Your performer issues a [com] challenge to target opposing character. They may move Home engaged. If they do, cancel the challenge.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01056(),
        ];
    }
}