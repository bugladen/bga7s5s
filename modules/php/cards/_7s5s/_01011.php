<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01011 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Servo Scarpa";
        $this->Image = "img/cards/7s5s/011.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 11;

        $this->Faction = "Vodacce";
        $this->Title = "Haughty Heir";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Deulist",
            "Red Hand",
            "Vodacce",
        ];

        $this->Actions = [
            new Action_01011(),
        ];

        $this->Techniques = [
            new Technique_01011(),
        ];

    }
}