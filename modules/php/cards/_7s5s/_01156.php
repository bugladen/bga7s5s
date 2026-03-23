<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01156;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01156 extends FactionAttachment implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Matchlock Musket");
        $this->Image = "01156.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 5;

        $this->Traits = [
            'Weapon',
            'Ranged',
            'Rifle',
        ];

        $this->Text = "<p>City Action: Discard a card • Target character at an adjacent City location may engage. If they do not, wound them. (They cannot engage if they are already engaged.)</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01156(),
        ];
    }
}