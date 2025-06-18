<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction_01043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;

// Having the controller choose to make Uwe a Mercenary is implemented as a reaction.
// Consider and add any situations where controller has choice to make Uwe a Mercenary.
// Make sure to set priority of reaction transitions to max so the choice is processed before the effect.

class _01043 extends Character implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Uwe Zimmerman";
        $this->Image = "img/cards/7s5s/043.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 43;

        $this->Faction = "Eisen";
        $this->Title = "The Unbroken Will";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->resetModifiedCharacterStats();
        
        $this->Traits = [
            "Hunter",
            "Eisen",
        ];

        $this->Reactions = [
            new Reaction_01043(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->actorId == $this->Id)
        {
            $adversary = $event->theah->getCharacterById($event->adversaryId);
            if (in_array("Sorcerer", $adversary->Traits))
            {
                $event->thrust += 1;
                $event->explanations[] = "Uwe's ability allows him to add 1 to his thrust when dueling with a Sorcerer.";
            }
        }


        // At the end of the player turn Uwe resets to not being a Mercenary
        if ($event instanceof EventPlayerTurnEnd)
        {
            $this->Traits = array_filter($this->Traits, fn($trait) => $trait != "Mercenary");
            $this->IsUpdated = true;
        }
    }

}