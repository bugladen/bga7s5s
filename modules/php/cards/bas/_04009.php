<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04009 extends Risk implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Rattle the Rigging");
        $this->Image = "04009.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 9;

        $this->initializeFaction("Vodacce");

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate("Challenge"),
            clienttranslate("Provocation"),
        ];

        $this->Text = clienttranslate("<p><b>En Garde Action:</b> Target opponent chooses one of their characters opposing your performer. The chosen character issues a [Combat] challenge to your performer. If your performer is a <b>Duelist</b>, their first combat card gains +1[Riposte].</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04009(),
        ];
    }
}
