<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _01038 extends Character implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Otto Streit");
        $this->Image = "01038.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 38;

        $this->initializeFaction("Eisen");
        $this->Title = "Industrous Ironmonger";
        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            "Academic",
            "Eisen",
        ];

        $this->Text = "<p>City Action: Reveal the top three cards of your deck. Put a revealed attachment into your hand. Sink the rest.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01038(),
        ];
    }

}