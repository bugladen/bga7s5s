<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01063;

class _01063 extends Character implements IHasTechniques
{
    use TechniqueTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Bastian Girard";
        $this->Image = "img/cards/7s5s/063.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 63;

        $this->Faction = "Montaigne";
        $this->Title = "Worldly Wit";
        $this->Resolve = 3;
        $this->Combat = 2;
        $this->Finesse = 4;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Duelist",
            "Musketeer",
            "Montaigne",
        ];

        $this->Techniques = [
            new Technique_01063(),
        ];

    }
}