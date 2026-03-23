<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01130;

class _01130 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Indomitable Will");
        $this->Image = "01130.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->initializeFaction("Ussura");

        $this->WealthCost = 0;
        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            'Immovable',
            'Provocation',
        ];

        $this->Text = "<p>City Action: If your performer's location is uncontrolled and they are your only character there • Claim it. You cannot lose control of it for as long as your performer is there. If your performer leaves this location, it becomes uncontrolled.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01130(),
        ];
    }

}