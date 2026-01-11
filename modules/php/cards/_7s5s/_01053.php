<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class _01053 extends Risk implements IHasReactions
{
    use ReactionTrait;
    
    private bool $EscapeDuel = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Hexenjagd");
        $this->Image = "01053.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 53;

        $this->initializeFaction('Eisen');

        $this->Riposte = 0;
        $this->Parry = 3;
        $this->Thrust = 0;

        $this->WealthCost = 0;

        $this->Traits = [
            'Hunt',
            'Zeal',
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01053(),
        ];

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

            $game = $event->theah->game;
            $actor = $event->theah->getDuelRoundActor();
            $adversary = $event->theah->getDuelRoundOpponent();

            if (! $game->characterIsInDiscardOrLocker($actor) && ! $adversary->hasTrait("Sorcerer"))
            {
                $game->notify->all("message", clienttranslate('${card_inject_code} activates: ${character_name} engages and is moved to Home.'), [
                    "card_inject_code" => $this->getInjectCode(),
                    "character_name" => $actor->Name,
                ]);
    
                $moveEvent = EventFactory::createCardMovingEvent($actor->ControllerId, $actor->Id, $actor->Location, Game::LOCATION_PLAYER_HOME, $engage = true, $this->Id);
                $event->theah->queueEvent($moveEvent);
            }
        }
    }
}