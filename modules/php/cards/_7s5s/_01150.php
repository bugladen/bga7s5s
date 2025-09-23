<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01150 extends Scheme
{
    public Array $interveneList = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Parley Gone Wrong");
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

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $game = $event->theah->game;

            $game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves. A Reknown will be added to the The Forum.  Opponents MAY then choose a city location. One Reknown will move from chosen location to The Forum.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_FORUM;
                $reknown->amount = 1;
                $reknown->description = $this->getInjectCode();
            }
            $event->theah->queueEvent($reknown);

            $players = $game->loadPlayersBasicInfos();

            //For each opponent, create an event that transitions to the state where they can choose a location to remove reknown from.
            foreach ($players as $playerId => $player) {
                if ($player['player_id'] == $this->OwnerId) continue;

                $transition = EventFactory::createTransitionEvent($playerId, $this->Id, '01150');
                $event->theah->queueEvent($transition);
            }
        }

        //When a player adds Reknown to The Forum, they may intervene this turn
        if ($event instanceof EventReknownAddedToLocation && $this->Location == Game::LOCATION_PLAYER_HOME && $event->location == Game::LOCATION_CITY_FORUM) 
        {
            $game = $event->theah->game;
            if ( $event->playerId != 0 && ! in_array($event->playerId, $this->interveneList)) {
                $this->interveneList[] = $event->playerId;
                $this->IsUpdated = true;
            }

            $playerNames = [];
            foreach ($this->interveneList as $playerId) {
                $playerNames[] = $game->getPlayerNameById($playerId);
            }
            $playerNames = implode(", ", $playerNames);

            $game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code}: ${player_names} has/have added Reknown to The Forums and may intervene this turn.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_names" => $playerNames
            ]);
        }

        //Reset the intervene list at the end of the day
        if ($event instanceof EventDuskEndOfDay)
        {
            $this->interveneList = [];
            $this->IsUpdated = true;
        }
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        //Only allow intervene at The Forum if the player contributed Reknown to The Forum
        if ($event instanceof EventCharacterIntervened && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $game = $event->theah->game; 
            
            //We are going to use the old target's location as the Challenge Location
            $oldTarget = $event->theah->getCharacterById($event->oldTargetId);
            if ($oldTarget->Location == Game::LOCATION_CITY_FORUM)
            {
                $playerId = $event->playerId;

                if ( ! in_array($playerId, $this->interveneList))
                {
                    throw new \BgaUserException($game->translate("Parley Gone Wrong: You cannot intervene at The Forum because you did not contribute Reknown to The Forum."));
                }
            }
        }
    }
    
    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01150)
        {
            $playerName = $game->getActivePlayerName();

            $location = $ids[0];
    
            $playerId = $game->getActivePlayerId();
            $removeEvent = EventFactory::createReknownRemovedFromLocationEvent($playerId, $location, 1, $playerName);
            $game->theah->eventCheck($removeEvent);
    
            $addEvent = EventFactory::createReknownAddedToLocationEvent($playerId, Game::LOCATION_CITY_FORUM, 1, $playerName);
            $game->theah->eventCheck($addEvent);
    
            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);
    
            $game->gamestate->nextState("");
    
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01150)
        {
            $game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code}: ${player_name} has passed choosing a location to remove reknown from.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getActivePlayerName()
            ]);

            $game->gamestate->nextState();
        }
    }
}