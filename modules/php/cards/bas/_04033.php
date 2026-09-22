<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class _04033 extends Character
{
    // WHY: Track apply so we only removeTrait what we granted (and once).
    public bool $DuelTraitsApplied = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Iago Carlos de Soldano");
        $this->Title = clienttranslate("La Navaja");
        $this->Image = "04033.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 33;

        $this->initializeFaction("Castille");

        $this->InPlayXImageOffset = 10;

        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 3;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Duelist"),
            clienttranslate("Academic"),
            clienttranslate("Razor"),
            clienttranslate("Castille")
        ];

        $this->Text = clienttranslate("<p>While Iago is participating in a duel, he gains <b>Pirate</b> and <b>Scoundrel</b>.</p>
<p><b>Technique:</b> +1[Thrust] or +1[Parry]. At the start of the adversary's next round, you may add a threat to Iago.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_04033(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelStarted)
        {
            if ($event->challengerId == $this->Id || $event->defenderId == $this->Id)
            {
                $this->applyDuelTraits($event);
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->clearDuelTraits($event);
        }

        if ($event instanceof EventDefenderSwapped)
        {
            if ($event->newDefenderId == $this->Id)
            {
                $this->applyDuelTraits($event);
            }
            else if ($event->oldDefenderId == $this->Id)
            {
                $this->clearDuelTraits($event);
            }
        }

        if ($event instanceof EventChallengerSwapped)
        {
            if ($event->newChallengerId == $this->Id)
            {
                $this->applyDuelTraits($event);
            }
            else if ($event->oldChallengerId == $this->Id)
            {
                $this->clearDuelTraits($event);
            }
        }
    }

    private function applyDuelTraits(Event $event): void
    {
        if ($this->DuelTraitsApplied)
        {
            return;
        }

        if ($this->ControllerId == 0 || $event->theah->game->characterIsInDiscardOrLocker($this))
        {
            return;
        }

        $this->addTrait($event->theah->game, "Pirate");
        $this->addTrait($event->theah->game, "Scoundrel");
        $this->DuelTraitsApplied = true;
        $this->IsUpdated = true;
    }

    private function clearDuelTraits(Event $event): void
    {
        if (! $this->DuelTraitsApplied)
        {
            return;
        }

        $this->removeTrait($event->theah->game, "Pirate");
        $this->removeTrait($event->theah->game, "Scoundrel");
        $this->DuelTraitsApplied = false;
        $this->IsUpdated = true;
    }
}
