<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01116a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01116b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _01116 extends Leader implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Yevgeni");
        $this->Image = "01116.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 116;

        $this->initializeFaction("Ussura");
        $this->Title = clienttranslate("The Boar");
        $this->Resolve = 12;
        $this->Combat = 4;
        $this->Finesse = 2;
        $this->Influence = 1;
        $this->CrewCap = 5;
        $this->Panache = 5;

        $this->Traits = [
            clienttranslate("Leader"),
            clienttranslate("Exile"),
            clienttranslate("Hero"),
            clienttranslate("Sorcerer"),
            clienttranslate("Ussura"),
        ];

        $this->Text = clienttranslate("<p>When Yevgeni plays a combat card, it gains +1[thrust].</p><p>Reaction: After Yevgeni's challenge is refused • En garde him.</p><p>Reaction: While paying for a non-character card • It has -1 cost.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01116a(),
            new Reaction_01116b(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->actorId == $this->Id)
        {
            $event->explanations[] = sprintf($event->theah->game->translate("%s increases his own Thrust values by +1"), $this->getInjectCode());
            $event->addThrust(1);
        }
    }

}