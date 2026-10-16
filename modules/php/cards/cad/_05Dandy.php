<?php

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _05Dandy extends Leader
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Claude de la Roche";
        $this->Title = "Disgraced Dandy";
        $this->Image = "05Dandy.v1.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        $this->CardNumber = 14;

        $this->initializeFaction("Montaigne");

        $this->Resolve = 7;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 4;

        $this->CrewCap = 7;
        $this->Panache = 6;

        $this->Traits = [
            "Leader",
            "Scoundrel",
            "Diplomat",
            "Merchant",
            "Montaigne"
        ];

        $this->Text = "<p>During setup, draw four cards. <i>(After shuffling)</i></p>
        <p>Claude has -1 [Influence] for each Renown at his location.<p>
        <p><b>Action:</b> Pay [1 Wealth] • Move Claude to any <b>City</b> location.</p>
        <p><b>Action:</b> Pay [1 Wealth] • Collect a Renown from this location.</p>";

        $this->resetCard();
    }
}