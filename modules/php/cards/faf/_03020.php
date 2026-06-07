<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03020 extends Risk implements IHasActions, IHasReactions, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Commanding');
        $this->Image = '03020.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 20;

        $this->initializeFaction('Eisen');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Virtue'),
            clienttranslate('Stalwart')
        ];

        $this->Text = clienttranslate("<p><b>Leader Action:</b> Target an opposing character • Move them Home.</p>
        <p><b>Leader Reaction:</b> When Renown would be moved from this location • Cancel the movement.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03020(),
        ];

        $this->Reactions = [
            new Reaction_03020(),
        ];
    }
}
