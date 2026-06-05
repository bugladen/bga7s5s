<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01049 extends FactionAttachment implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Polished Flintlock");
        $this->Image = "01049.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 49;
        $this->initializeFaction('Eisen');
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 1;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 2;
        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Ranged'),
            clienttranslate('Pistol'),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Target an opposing character • They may engage. If they do not, wound them and engage this card.</p><p><b>Technique:</b> Engage this card • Gain Lethal.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_01049(),
        ];

        $this->Actions = [
            new Action_01049(),
        ];
    }
}