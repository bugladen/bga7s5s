<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01160 extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Bleed Out');
        $this->Image = '01160.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 160;

        $this->Riposte = 1;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 3;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Villainous'),
        ];

        $this->Text = clienttranslate("<p>While your Leader is a Villain, this card has -1 cost.</p><p>Action: Target a wounded non-Leader character in a City location • Wound them.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01160(),
        ];
    }
}