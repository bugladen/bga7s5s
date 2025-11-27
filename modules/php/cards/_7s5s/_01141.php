<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01141;

class _01141 extends Risk implements IHasActions
{
    use ActionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Strong Hands");
        $this->Image = "img/cards/7s5s/141.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = "Ussura";

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 4;

        $this->Traits = [
            'Brawl',
            'Kulachniy Boi',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01141(),
        ];
    }
}