<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02058;

class _02058 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Heroic Intervention');
        $this->Image = '02058.jpg';
        $this->ExpansionName = 'tac';
        $this->ExpansionNumber = 2;
        $this->CardNumber = 58;

        $this->initializeFaction('Neutral');

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->WealthCost = 1;

        $this->Traits = [
            clienttranslate('Heroic'),
            clienttranslate('Duty')
        ];

        $this->Text = clienttranslate("<p>If your performer is a <b>Hero</b> or <b>Knight</b>, this card has -1 cost.</p><p><b>Reaction:</b> After a challenge is issued, engage your adjacent performer • Move them to that location and they intervene.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02058(),
        ];
    }
}