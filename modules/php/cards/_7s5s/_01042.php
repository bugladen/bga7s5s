<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;


class _01042 extends Character implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Terrell Brandt";
        $this->Image = "img/cards/7s5s/042.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 42;

        $this->Faction = "Eisen";
        $this->Title = "Instrument of Iron";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Duelist",
            "Eisen",
        ];

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_01042");
        $technique->Name = "Terrell Brandt: +1 Thrust";        
        $this->Techniques = [
            $technique,
        ];
    }
}