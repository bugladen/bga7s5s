<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01189a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01189b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01189 extends CityEventCard implements IHasActions
{
    use ActionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = 'Point of Opportunity';
        $this->Image = "img/cards/7s5s/189.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 189;
        
        $this->CityCardNumber = 13;

        $this->Traits = [
            'Duress',
            'Fortune',
        ];

        $this->Actions = [
            new Action_01189a(),
            new Action_01189b(),
        ];
    }
}