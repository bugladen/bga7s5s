<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01122;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _01122 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Torsten Vakt");
        $this->Image = "img/cards/7s5s/122.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 122;

        $this->initializeFaction("Ussura");
        $this->Title = "Incorrigible Drunk";
        $this->Resolve = 6;
        $this->Combat = 3;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            "Scoundrel",
            "Murskaaja",
            "Vesten",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01122(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->actorId == $this->Id && $this->Wounds >= 2)
        {
            $event->explanations[] = sprintf($event->theah->game->translate("%s increases his own Thrust values by +1 by having 2 or more Wounds."), $this->getInjectCode());
            $event->addThrust(1);
        }
    }

}