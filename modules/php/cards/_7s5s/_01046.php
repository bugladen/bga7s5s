<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01046a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01046b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01046 extends FactionAttachment implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Dark Gift");
        $this->Image = "01046.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction('Eisen');
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;
        $this->Riposte = 3;
        $this->Parry = 0;
        $this->DashedParry = true;
        $this->Thrust = 0;

        $this->Traits = [
            'Sorcery',
            'Corruption',
            'Unique',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01046a(),
            new Action_01046b(),
        ];
    }

}