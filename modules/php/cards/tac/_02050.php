<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02050;

class _02050 extends Risk implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ussuran Intrigue");
        $this->Image = "02050.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 50;

        $this->initializeFaction("Ussura");

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->WealthCost = 0;

        $this->Traits = [
            clienttranslate('Cunning'),
            clienttranslate('Beauracracy'),
        ];

        $this->Text = clienttranslate("<p><b>City Reaction:</b> When any player moves or adds any amount of Renown to any location • Pressure your performer's location with [Influence]. You succeed even if tied. If successful, move one of that Renown to their location instead. <i>(Even during Planning.)</i></p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02050(),
        ];
    }
}