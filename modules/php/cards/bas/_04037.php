<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04037 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cats in Every Corner");
        $this->Image = "04037.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 37;

        $this->initializeFaction("Castille");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 2;
        $this->Thrust = 0;
        $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate("Ad Hoc"),
            clienttranslate("Discovery")
        ];

        $this->Text = clienttranslate("<p><b>En Garde Academic Action:</b> Discard an available City Card at this location • Look at the top five cards of the City Deck. You may add one to this location, then sink the rest. Then, your performer may perform another action. <i>(It must be performed and they must be the performer)</i></p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04037(),
        ];
    }
}
