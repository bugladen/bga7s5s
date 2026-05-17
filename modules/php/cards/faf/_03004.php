<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03004 extends Character
{
    public int $FinesseBonus = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Elena Agnelli");
        $this->Title = clienttranslate("Hungry Soul");
        $this->Image = "03004.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 4;

        $this->initializeFaction("Vodacce");

        $this->Resolve = 5;
        $this->Combat = 2;
        $this->Finesse = 0;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Sorcerer"),
            clienttranslate("Strega"),
            clienttranslate("Pirate"),
            clienttranslate("Spy"),
            clienttranslate("Vodacce")
        ];

        $this->Text = clienttranslate("<p>Elena has +1[Finesse] for each <b>Sorcery</b> in her dueling line.</p><p><b>Technique:</b> If Elena's combat card is a <b>Sorcery</b> • +1[Parry] and wound the adversary.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_03004(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($this->ControllerId == 0)
        {
            return;
        }

        if ($event instanceof EventDuelEndOfRound)
        {
            $this->recomputeFinesseBonus($event->theah);
        }

        if ($event instanceof EventDuelEnd)
        {
            // Reset before the dueling line is cleared so the bonus is undone
            // even if the dueling line still happens to contain Sorcery cards.
            $this->applyFinesseDelta(0, $event->theah);
        }
    }

    private function recomputeFinesseBonus(Theah $theah): void
    {
        // "Her dueling line" — Elena must be a participant of the current duel.
        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();
        if ($this->Id != $challengerId && $this->Id != $defenderId)
        {
            $this->applyFinesseDelta(0, $theah);
            return;
        }

        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $this->ControllerId);
        $count = 0;
        foreach ($cards as $card)
        {
            if ($card->hasTrait("Sorcery"))
            {
                $count++;
            }
        }

        $this->applyFinesseDelta($count, $theah);
    }

    private function applyFinesseDelta(int $newBonus, Theah $theah): void
    {
        $delta = $newBonus - $this->FinesseBonus;
        if ($delta == 0)
        {
            return;
        }

        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $this->ControllerId,
            $this->Id,
            $this->ModifiedFinesse,
            $this->ModifiedFinesse + $delta,
            $this->getInjectCode()
        );
        $theah->queueEvent($finesseEvent);

        $this->FinesseBonus = $newBonus;
        $this->IsUpdated = true;
    }
}
