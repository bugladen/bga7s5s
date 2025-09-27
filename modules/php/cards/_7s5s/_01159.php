<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01159;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01159 extends Risk implements IHasActions
{
    use ActionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Appealing to the People");
        //There are two versions of this card with different pictures.  The overriding classes will set the correct picture.
        //$this->Image = "img/cards/7s5s/159.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 2;
        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            'Beauracracy',
            'Heroic',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01159(),
        ];
    }
}