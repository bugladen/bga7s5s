<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05RedTape;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _05RedTape extends Risk implements IHasActions, IRiskThatTargetsCharacters
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Customs Inspection";
        $this->Image = "05RedTape.v5.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 26;

        $this->initializeFaction("Castille");

        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        // WHY: Bureaucracy (TraitNames) — stub typo was Beaurocracy (same as Leverage hygiene).
        $this->Traits = [
            "Bureaucracy",
            "Duress"
        ];

        $this->Text = "<p><b>Academic Action:</b> Target an opposing engaged character • Their controller reveals four cards from hand. Choose a revealed card and sink it.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_05RedTape(),
        ];
    }
}
