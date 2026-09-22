<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04056a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04056b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _04056 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Greed');
        $this->Image = '04056.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 56;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Envy'),
            clienttranslate('Covetous'),
            clienttranslate('Villainous')
        ];

        $this->Text = clienttranslate("<p><b>Reaction:</b> When a Renown would be moved to or from your performer's location • Cancel the movement.</p>
<p><b>En Garde Reaction:</b> When a player would collect Renown from your performer's location • They collect one fewer. <i>(Even during Plunder.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_04056a(),
            new Reaction_04056b(),
        ];
    }
}
