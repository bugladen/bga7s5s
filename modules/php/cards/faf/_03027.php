<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03027a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03027b;

class _03027 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Odette Dubois D'Arrent");
        $this->Title = clienttranslate('Disillusioned Courtier');
        $this->Image = '03027.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 27;

        $this->initializeFaction('Montaigne');

        $this->Resolve = 3;
        $this->Combat = 0;
        $this->Finesse = 3;
        $this->Influence = 3;

        $this->Traits = [
            clienttranslate('Hero'),
            clienttranslate('Diplomat'),
            clienttranslate('Spy'),
            clienttranslate('Montaigne')
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> After another character at this location is destroyed • Odette heals a wound. Then, you may move an adjacent Renown to this location.</p>
<p><b>City Reaction:</b> After a challenge is issued at this location • Move your adjacent <b>Duelist</b> to this location. <i>(Before choosing to intervene.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03027a(),
            new Reaction_03027b(),
        ];
    }
}