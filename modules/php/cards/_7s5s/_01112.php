<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01112a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01112b;

class _01112 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Carnaval");
        $this->Image = "img/cards/7s5s/112.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Castille";
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->Traits = [
            'Revelry',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01112a(),
            new Action_01112b(),
        ];
    }
}