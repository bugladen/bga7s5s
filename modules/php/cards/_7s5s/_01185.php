<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01185;
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
        $this->Image = "01185.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 185;

        $this->CityCardNumber = 9;

        $this->Traits = [
            'Discovery',
            "Explorer's Society",
            'Fortune',
        ];

        $this->Text = clienttranslate("<p>City Action: Discard two cards • Add a Renown to this location. Discard this card.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01185(),
        ];
    }
}