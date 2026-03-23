<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01123;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01123 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Valeri Mikhailov");
        $this->Image = "01123.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 123;

        $this->initializeFaction("Ussura");
        $this->Title = "Champion Narcissist";
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            "Duelist",
            "Ussura",
        ];

        $this->Text = clienttranslate("<p>Action: Engage Valeri and target an enemy character at an adjacent City location • Move Valeri there and issue them a [combat] challenge. Other characters cannot intervene.</p><p>Technique: +1 [Thrust]. If Valeri has fewer wounds than the adversary, +1 [Riposte] instead.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01123(),
        ];

        $this->Techniques = [
            new Technique_01123(),
        ];
    }
}