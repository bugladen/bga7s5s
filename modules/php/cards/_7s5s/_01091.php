<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01091;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01091 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('"Madre" Dolores');
        $this->Image = "01091.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 91;

        $this->initializeFaction("Castille");
        $this->Title = clienttranslate("Cat Lady of Castille");
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;       
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate("Academic"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p>City Action: Target a character at this location, or two characters instead by discarding a card • Heal a wound from each targeted character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01091(),
        ];
    }
}