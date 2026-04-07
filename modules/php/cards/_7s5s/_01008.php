<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;

class _01008 extends Character implements IHasActions, IHasReactions, IHasTechniques, IHasManeuvers
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Cesca Del Rosso";
        $this->Image = "01008.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 8;

        $this->initializeFaction("Vodacce");
        $this->Title = clienttranslate("Sadistic Weaver");
        $this->Resolve = 5;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Sorcerer"),
            clienttranslate("Strega"),
            clienttranslate("Red Hand"),
            clienttranslate("Vodacce"),
        ];

        $this->Text = clienttranslate("<p><b>Sorcerer CIty Action:</b> Reveal the top card of your deck. If it is a Sorcery, put it into your hand. If not, you may sink it.</p><p><b>Reaction:</b> After Cesca performed  a Sorcerer Ability, or one targeted a character at this location • Wound her. Cesca performs a copy of it, paying all costs. (Can choose new targets.)</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01008(),
        ];

        $this->Reactions = [
            new Reaction_01008(),
        ];
    }

}