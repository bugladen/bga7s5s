<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01011 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Servo Scarpa";
        $this->Image = "01011.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 11;

        $this->initializeFaction("Vodacce");
        $this->Title = "Haughty Heir";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Deulist",
            "Red Hand",
            "Vodacce",
        ];

        $this->Text = "<p>Servo may issue challenges to characters opposing your Red Hands at locations adjacent to him. When he does, move him there.</p><p>Technique: +1 [Thrust] for each of your other Red Hands at this location.</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01011(),
        ];

        $this->Techniques = [
            new Technique_01011(),
        ];

    }
}