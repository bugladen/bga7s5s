<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01201;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01201 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ravenna Destine");
        $this->Image = "01201.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 201;

        $this->Title = 'Doomsayer';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->WealthCost = 4;
        $this->CityCardNumber = 25;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Sorcerer',
            'Strega',
            'Vodacce',
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01201(),
        ];
    }
}