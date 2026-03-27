<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01068 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Léontine Giroux");
        $this->Image = "01068.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 68;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("Lithe Lioness");
        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Musketeer"),
            clienttranslate("Sorcerer"),
            clienttranslate("Montaigne"),
        ];

        $this->Text = clienttranslate("<p>Sorcerer City Action: Wound Léontine • Move target character you control from her location to another.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01068(),
        ];
    }

}