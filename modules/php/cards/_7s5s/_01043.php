<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

// WHY hasTrait override instead of a reaction: The Mercenary trait check happens during
// isAvailableToPlayer / getPerformersForAction (card selection phase), which runs before
// events. Reactions can only fire in response to events, so they can't pre-empt trait checks.
// The hasTrait override with $queryCard is the only mechanism that works at selection time.
// Callers already validate ownership (ControllerId), so no ownership check needed here.

class _01043 extends Character
{

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Uwe Zimmerman");
        $this->Image = "01043.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 43;

        $this->initializeFaction("Eisen");
        $this->Title = clienttranslate("The Unbroken Will");
        $this->Resolve = 5;
        $this->Combat = 3;
        $this->Finesse = 2;
        $this->Influence = 1;

        $this->Traits = [
            clienttranslate("Hunter"),
            clienttranslate("Eisen"),
        ];

        $this->Text = clienttranslate("<p>While using your abilities, Uwe may be considered a Mercenary. (For costs and effects.)</p><p>While the adversary is a Sorcerer, Uwe's combat cards gain +1 [Thrust] .</p>");

        $this->resetCard();
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
    }

    // WHY: Cards that benefit from Uwe being a Mercenary pass themselves as $queryCard.
    // Add new entries here when adding cards whose abilities check for Mercenary via $queryCard.
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