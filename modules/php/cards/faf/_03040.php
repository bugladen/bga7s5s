<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;

class _03040 extends Character implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Soline el Gato');
        $this->Title = clienttranslate('Gato el la Bolsa');
        $this->Image = '03040.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 40;

        $this->initializeFaction('Castille');

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate('Scoundrel'),
            clienttranslate('Pirate'),
            clienttranslate('Thief'),
            clienttranslate('Castille')
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> After a character moves to this location • Move Soline to any <b>City</b> location.</p>
<p><b>City Action:</b> Engage Soline • Pressure this location with [Finesse]. You succeed even if tied. If successful, claim it or engage an opposing character.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03040(),
        ];

        $this->Reactions = [
            new Reaction_03040(),
        ];
    }
}
