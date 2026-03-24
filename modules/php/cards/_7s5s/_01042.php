<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01042 extends Character implements IHasTechniques
{
    use TechniqueTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Terrell Brandt");
        $this->Image = "01042.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 42;

        $this->initializeFaction("Eisen");
        $this->Title = "Instrument of Iron";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 1;

        $this->Traits = [
            "Duelist",
            "Eisen",
        ];

        $this->Text = clienttranslate("<p>When Terrell's duel ends, attachments in his dueling line go into your hand instead of the discard pile. (They do not if Terrell was destroyed.)</p><p>Technique: +1 [Thrust].</p>");

        $this->resetCard();

        $technique = new Technique_PlusOneThrust();
        $technique->setId("Technique_01042");
        $this->Techniques = [
            $technique,
        ];
    }
}