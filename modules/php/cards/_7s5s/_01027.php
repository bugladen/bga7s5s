<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _01027 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Objection!');
        $this->Image = '01027.jpg';
        $this->ExpansionName = '_7s5s';
        $this->ExpansionNumber = 1;
        $this->CardNumber = 27;

        $this->initializeFaction('Vodacce');

        $this->Riposte = 2;
        $this->Parry = 0;
        $this->Thrust = 1;

        $this->WealthCost = 0;

        $this->Traits = [
            'Bureaucracy',
            'Cunning',
        ];

        $this->Text = clienttranslate("<p>Reaction: When a pressure succeeds with a difference of 1 or less • The pressure fails instead.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01027()
        ];
    }
}