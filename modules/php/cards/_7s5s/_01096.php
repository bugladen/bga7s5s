<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01096;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01096;

class _01096 extends Character implements IHasActions, IHasTechniques
{
    use ActionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ratón");
        $this->Image = "01096.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 96;

        $this->initializeFaction("Castille");
        $this->Title = "The Gentleman";
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 1;

        $this->Traits = [
            "Pirate",
            "Scoundrel",
            "Thief",
            "Castille",
        ];

        $this->Text = "<p>City Action: Target an adjacent enemy character equipped with attachment • Move Ratón to their location.</p><p>Technique: At the end of the adversary's next round, if they were not wounded during it, take control of target attachment on them and equip it to Ratón. (There are no adversaries if the challenge is refused.)</p>";

        $this->resetCard();

        $this->Actions = [
            new Action_01096(),
        ];

        $this->Techniques = [
            new Technique_01096(),
        ];
    }
}
