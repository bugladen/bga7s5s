<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01043;
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

        $this->Name = clienttranslate("Uwe Zimmerman");
        $this->Image = "01043.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 43;

        $this->initializeFaction("Eisen");
        $this->Title = "The Unbroken Will";
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            "Hunter",
            "Eisen",
        ];

        $this->Text = clienttranslate("<p>While using your abilities, Uwe may be considered a Mercenary. (For costs and effects.)</p><p>While the adversary is a Sorcerer, Uwe's combat cards gain +1 [Thrust] .</p>");

        $this->resetCard();

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
            if ($adversary->hasTrait("Sorcerer"))
            {
                $event->explanations[] = sprintf($event->theah->game->translate("%s increases the Thrust of his Combat Cards by 1 when dueling with a Sorcerer."), $this->getInjectCode());
                $event->addThrust(1);
            }
        }


        // At the end of the player turn Uwe resets to not being a Mercenary
        if ($event instanceof EventPlayerTurnEnd)
        {
            $this->ModifiedTraits = array_filter($this->ModifiedTraits, fn($trait) => $trait != "Mercenary");
            $this->IsUpdated = true;
        }
    }

    // When adding new cards that query for Mercenary traits, add here if it would benefit player
    public function hasTrait(string $trait, ?Card $queryCard = null): bool
    {
        if ($trait == "Mercenary")
        {
            return
            (
                parent::hasTrait($trait, $queryCard)
             || $queryCard instanceof _01036
             || $queryCard instanceof _01039
             || $queryCard instanceof _01051
            );
        }

        return parent::hasTrait($trait, $queryCard);
    }

}