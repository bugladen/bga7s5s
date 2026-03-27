<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class _01121 extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Ren");
        $this->Image = "01121.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 121;

        $this->initializeFaction("Ussura");
        $this->Title = clienttranslate("Graven Pendant");
        $this->Resolve = 4;
        $this->Combat = 3;
        $this->Finesse = 3;
        $this->Influence = 0;

        $this->Traits = [
            clienttranslate("Hero"),
            clienttranslate("Academic"),
            clienttranslate("Duelist"),
            clienttranslate("Shenzhou")
        ];

        $this->Text = clienttranslate("<p>While Ren's adversary controls equal or more characters than you, her adversary's combat cards have -1[parry]. (Anything less than 0 is treated as a 0.)</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->adversaryId == $this->Id)
        {
            $actor = $event->theah->getCharacterById($event->actorId);
            $adversaryCount = count($event->theah->getCharactersInPlayByPlayerId($actor->ControllerId));
            $characterCount = count($event->theah->getCharactersInPlayByPlayerId($this->ControllerId));
            if ($adversaryCount >= $characterCount)
            {
                $event->explanations[] = sprintf($event->theah->game->translate("%s decreases her Adversary's Parry values by -1 if her controller's Adversary controls equal or more characters."), $this->getInjectCode());
                $event->removeParry(1);
            }
        }
    }
}