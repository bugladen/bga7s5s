<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01163;

class _01163 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Devotion");
        $this->Image = "01163.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate('Faith'),
        ];

        $this->Text = clienttranslate("<p>Action: Look at the top three cards of your deck. Sink one, put one into your hand, and add the last to a location facedown. At the end of the day, if you control that location, put the added card into your hand. Otherwise, discard it.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01163(),
        ];
    }
}