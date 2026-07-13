<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03046a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03046b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03046 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Passionate");
        $this->Image = '03046.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 46;

        $this->initializeFaction("Castille");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate("Virtue")
        ];

        $this->Text = clienttranslate("<p><b>Duelist Reaction:</b> After your performer intervenes • En garde them.</p>
        <p><b>Pirate Reaction:</b> After your performer's challenge is accepted, if their adversary intervened • En garde your performer.</p>");
        
        $this->resetCard();

        $this->Reactions = [
            new Reaction_03046a(),
            new Reaction_03046b(),
        ];
    }
}
