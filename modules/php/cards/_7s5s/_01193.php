<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01193;

class _01193 extends CityAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Burnished Cuirass');
        $this->Image = "01193.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 193;
        
        $this->CityCardNumber = 17;
        $this->WealthCost = 1;

        $this->ResolveModifier = 1;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            clienttranslate('Armor'),
        ];

        $this->Text = clienttranslate("<p>Technique: During the adversary's next round, their combat card has -1 [Thrust].</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_01193(),
        ];
    }
}