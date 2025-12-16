<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01025 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Fate's Burden";
        $this->Image = 'img/cards/7s5s/025.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 25;

        $this->Faction = 'Vodacce';

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->WealthCost = 0;

        $this->Traits = [
            'Sorcery',
            'Sorte',
        ];
 
        $this->resetCard();

        $this->Actions = [
            new Action_01025(),
        ];
   }
}