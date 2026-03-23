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

        $this->Name = clienttranslate("Langschwert");
        $this->Image = "01048.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Eisen');
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Weapon',
            'Melee',
            'Sword',
        ];

        $this->Text = "<p>Technique: +1 [Thrust].</p><p>Technique: +1 [Thrust].</p>";

        $this->resetCard();

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_01048_1");
        $this->Techniques[] = $technique;

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_01048_2");
        $this->Techniques[] = $technique;
    }
}