<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01130;

class _01130 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Indomitable Will");
        $this->Image = "img/cards/7s5s/130.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;

        $this->Traits = [
            'Immovable',
            'Provocation',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01130(),
        ];
    }

}