<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01161 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Boon");
        $this->Image = "01161.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            'Sorcery',
            'Glamour',
        ];

        $this->Text = clienttranslate("<p>Sorcerer City Action: Engage your performer • Equip this card to a character at this location. This ability cannot be copied.</p><p>The equipped character gains: \"+1 [Combat], [Finesse], and [Influence]. At the end of the Day, discard this card.\"</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01161(),
        ];
    }
}