<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;

class _03068 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Confusion");
        $this->Image = '03068.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 68;

        $this->initializeFaction('Neutral');
        
        $this->WealthCost = 0;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 2;

        $this->Traits = [
            clienttranslate('Hubris'),
            clienttranslate('Duress')
        ];

        $this->Text = clienttranslate("<b>City Reaction:</b> After an opponent passes • They must move an en garde character from their <b>Home</b> to a <b>City</b> location.");   

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03068(),
        ];
    }
}
