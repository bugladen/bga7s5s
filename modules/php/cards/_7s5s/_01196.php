<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_01196;

class _01196 extends CityCharacter implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Angeline Dèmone';
        $this->Image = "img/cards/7s5s/196.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 196;
        
        $this->Title = 'La Bouchere';

        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 5;
        $this->CityCardNumber = 20;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Pirate',
            'Sorcerer',
            'Montaigne',
        ];

        $this->Techniques = [
            new Technique_01196(),
        ];
    }
}