<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01185;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01185 extends CityEventCard implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Risky Undertaking");
        $this->Image = "img/cards/7s5s/185.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 185;

        $this->CityCardNumber = 9;

        $this->Traits = [
            'Discovery',
            "Explorer's Society",
            'Fortune',
        ];

        $this->Actions = [
            new Action_01185(),
        ];
    }
}