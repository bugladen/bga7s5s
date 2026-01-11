<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01093;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01093 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maya de La Rioja");
        $this->Image = "01093.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 93;

        $this->initializeFaction("Castille");
        $this->Title = "Amoral Compass";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;       
        $this->Influence = 1;

        $this->Traits = [
            "Duelist",
            "Pirate",
            "Castille",
        ];

        $this->resetCard();

        $this->Actions = [
            new Action_01093(),
        ];

        $this->Techniques = [
            new Technique_01093(),
        ];
    }
}