<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;

class _03002 extends Character implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Aja");
        $this->Title = clienttranslate("Vicious and Useful");
        $this->Image = "03002.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 2;

        $this->initializeFaction("Vodacce");

        $this->InPlayXImageOffset = -20;

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 4;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->Traits = [
            clienttranslate("Assassin"),
            clienttranslate("Duelist"),
            clienttranslate("Spy"),
            clienttranslate("Mbey"),
        ];

        $this->Text = clienttranslate("<p><b>City Action:</b> Engage Aja • Issue a <b>Combat</b> challenge to target opposing character. Only characters with 3 <b>Finesse</b> or more may intervene or refuse the challenge.</p><p><b>Gambling Technique:</b> If the adversary is wounded • Gain <b>Lethal</b>.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03002(),
        ];

        $this->Techniques = [
            new Technique_03002(),
        ];
    }
}
