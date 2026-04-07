<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02026a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02026b;

class _02026 extends FactionAttachment implements IHasTechniques
{
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Croc de Lion');
        $this->Image = "02026.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 26;

        $this->initializeFaction('Montaigne');

        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;
        $this->OffHand = true;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Melee'),
            clienttranslate('Dagger'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p>Offhand <i>(Offhand attachments do not count against the limit of one Armor and one Weapon per character. Limit one attachment with Offhand per character.)</i></p><p><b>Duelist Technique:</b> Engage target attachment equipped to the adversary.</p><p><b>Duelist Technique:</b> Destroy target engaged attachment equipped to the adversary.</p>");

        $this->resetCard();

        $techniqueA = new Technique_02026a();
        $techniqueA->setId("Technique_02026a");
        $techniqueB = new Technique_02026b();
        $techniqueB->setId("Technique_02026b");
        $this->Techniques = [
            $techniqueA,
            $techniqueB,
        ];
    }
}