<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01150 extends Scheme
{
    public Array $interveneList = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Parley Gone Wrong";
        $this->Image = "img/cards/7s5s/150.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 150;

        $this->Initiative = 55;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Feud", 
            "Provocation",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $game = $event->theah->game;

            $game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves. A Reknown will be added to the The Forum.  Opponents MAY then choose a city location. One Reknown will move from chosen location to The Forum.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_FORUM;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            $players = $game->loadPlayersBasicInfos();

            //For each opponent, create an event that transitions to the state where they can choose a location to remove reknown from.
            foreach ($players as $playerId => $player) {
                if ($player['player_id'] == $this->OwnerId) continue;

                $transition = $event->theah->createEvent(Events::Transition);
                if ($transition instanceof EventTransition) {
                    $transition->playerId = $playerId;
                    $transition->transition = '01150';
                }
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventReknownAddedToLocation && $event->location == Game::LOCATION_CITY_FORUM) {
            $game = $event->theah->game;
            if ( $event->playerId != 0 && ! in_array($event->playerId, $this->interveneList)) {
                $this->interveneList[] = $event->playerId;
                $this->IsUpdated = true;
            }

            $playerNames = [];
            foreach ($this->interveneList as $playerId) {
                    $playerName = $game->getUniqueValueFromDB("SELECT player_name from player where player_id = {$playerId}");
                    $playerNames[] = $playerName;
            }
            $playerName = implode(", ", $playerNames);

            $game->notifyAllPlayers("message", clienttranslate('${card_name}: ${player_name} has added Reknown to The Forums and may intervene this turn.'), [
                "card_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $playerName,
            ]);

        }
    }
}