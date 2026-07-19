<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _03064 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Harpoon");
        $this->Image = '03064.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 64;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Ranged'),
            clienttranslate('Harpoon'),
        ];

        $this->Text = clienttranslate("<b>Gambling Technique:</b> Engage this card • The adversary has -1[Finesse], cannot be swapped, and cannot move for the remainder of the duel.");

        $this->resetCard();

        $this->Techniques = [
            new Technique_03064(),
        ];
    }
}
