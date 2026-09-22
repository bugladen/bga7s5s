<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04054b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;

class _04054 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Sabre');
        $this->Image = '04054.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 54;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 1;
        $this->ResolveModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;
        $this->CombatModifier = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Melee'),
            clienttranslate('Sword')
        ];

        $this->Text = clienttranslate("<p><b>Technique:</b> +1[Thrust]</p>
<p><b>Technique:</b> Engage this card • +1[Riposte]</p>");

        $this->resetCard();

        // WHY: Distinct Id from ClassId so the generic PlusOneThrust instance is unique
        // on this card (Rapier _01074 / Millstone _04cd14 pattern).
        $thrust = new Technique_PlusOneThrust();
        $thrust->setId('Technique_04054a');

        $this->Techniques = [
            $thrust,
            new Technique_04054b(),
        ];
    }
}
