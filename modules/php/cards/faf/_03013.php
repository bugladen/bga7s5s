<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

class _03013 extends Leader implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Daniella Dietrich");
        $this->Title = clienttranslate("Witch, Hunter");
        $this->Image = "03013.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 13;

        $this->initializeFaction("Eisen");

        $this->Resolve = 6;
        $this->Combat = 2;
        $this->Finesse = 2;
        $this->Influence = 3;
        $this->CrewCap = 5;
        $this->Panache = 7;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Scoundrel"),
            clienttranslate("Strega"),
            clienttranslate("Sorcerer"),
            clienttranslate("Vodacce")
        ];

        $this->Text = clienttranslate("<p>While using your abilities, characters opposing Daniella may be considered <b>Sorcerers</b>. <i>(Action, Forced, Maneuver, Passive, Technique, and Reaction are abilities.)</i></p><p><b>Reaction:</b> While paying for a Faith or Sorcery card • It has -1 cost.</p><p><b>Technique:</b> Wound Daniella • Swap her with your Hunter or Zealot at this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03013(),
        ];

        $this->Reactions = [
            new Reaction_03013(),
        ];

        $this->Techniques = [
            new Technique_03013(),
        ];
    }

    public function handleEvent(Event $event)
    {
        // The continuous Sorcerer-trait passive is implemented by Action_03013.
        // This override still exists for Leader-level event handling (Panache
        // modifiers, leader-destroyed end-of-game) inherited via parent.
        parent::handleEvent($event);
    }
}
