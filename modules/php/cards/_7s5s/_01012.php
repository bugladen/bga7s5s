<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01012 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Sibella Scarpa";
        $this->Image = "img/cards/7s5s/012.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 12;

        $this->Faction = "Vodacce";
        $this->Title = "Deceitful Witch";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 3;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Sorcerer",
            "Strega",
            "Red Hand",
            "Vodacce",
        ];

        $this->Actions = [
            new Action_01012(),
        ];
    }

}