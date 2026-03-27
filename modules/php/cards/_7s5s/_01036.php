<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01036 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Daniella Dietrich");
        $this->Image = "01036.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 36;

        $this->initializeFaction("Eisen");
        $this->Title = clienttranslate("Rueful Confidante");
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Sorcerer"),
            clienttranslate("Strega"),
            clienttranslate("Vodacce"),
        ];

        $this->Text = clienttranslate("<p>City Action: Your Mercenary at this location issues a [com] challenge to target opposing character.</p><p>Technique: When your round ends, move Daniella to an adjacent City location. Usable once per Day.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01036(),
        ];

        $this->Techniques = [
            new Technique_01036(),
        ];
    }
}