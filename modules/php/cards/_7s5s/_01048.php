<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;

class _01048 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Langschwert";
        $this->Image = "img/cards/7s5s/048.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->Faction = 'Eisen';
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Sword',
        ];

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_01048_1");
        $technique->Name = "Langschwert: +1 Thrust";
        $this->Techniques[] = $technique;

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_01048_2");
        $technique->Name = "Langschwert: +1 Thrust";
        $this->Techniques[] = $technique;
    }
}