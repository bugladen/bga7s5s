<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class _01169 extends Risk
{
    private bool $EscapeDuel = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Not Today");
        $this->Image = "img/cards/7s5s/169.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->WealthCost = 0;
        $this->Riposte = 0;
        $this->Parry = 5;
        $this->Thrust = 0;

        $this->Traits = [
            'Ad Hoc',
        ];

        $this->resetCard();

        $this->EscapeDuel = false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->combatCardId == $this->Id)
        {
            $this->EscapeDuel = true;
            $this->IsUpdated = true;
        }

        if ($event instanceof EventDuelEndOfRound && $this->EscapeDuel)
        {
            $this->EscapeDuel = false;
            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${card_inject_code} activates: ${character_name} is wounded and is moved to Home.'), [
                "card_inject_code" => $this->getInjectCode(),
                "character_name" => $event->theah->getDuelRoundActor()->Name,
            ]);

            $actor = $event->theah->getDuelRoundActor();

            $woundEvent = EventFactory::createCharacterWoundedEvent($actor->Id, $this->Id, 1, $this->getInjectCode());
            $event->theah->queueEvent($woundEvent);

            $moveEvent = EventFactory::createCardMovedEvent($actor->ControllerId, $actor->Id, $actor->Location, Game::LOCATION_PLAYER_HOME, $engage = false, $this->Id);
            $event->theah->queueEvent($moveEvent);

            if (! $this->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($actor->ControllerId, $actor->Id, $this->Id);
                $event->theah->queueEvent($engageEvent);
            }
        }
    }
}