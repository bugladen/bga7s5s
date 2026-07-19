<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01150 extends Scheme
{
    public Array $interveneList = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Parley Gone Wrong");
        $this->Image = "01150.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 150;

        $this->Initiative = 55;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate("Feud"), 
            clienttranslate("Provocation"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The City Forum]. Then, each opponent may move a Renown from any location to [The City Forum].</p><hr><p>Players can intervene in challenges at [The City Forum] only if they added or moved a Renown there this Day. (Adding or moving a Renown during the Day counts.)</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to the The Forum.  Opponents MAY then choose a city location. One Renown will move from chosen location to The Forum.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            $players = $game->loadPlayersBasicInfos();

            //For each opponent, create an event that transitions to the state where they can choose a location to remove reknown from.
            foreach ($players as $playerId => $player) {
                if ($player['player_id'] == $this->ControllerId) continue;

                $transition = EventFactory::createTransitionEvent($playerId, $this->Id, '01150');
                $transition->priority = Event::MEDIUM_PRIORITY;
                $event->theah->queueEvent($transition);
            }
        }

        //When a player adds Reknown to The Forum, they may intervene this turn
        if ($event instanceof EventRenownAddedToLocation && $this->Location == Game::LOCATION_PLAYER_HOME && $event->location == Game::LOCATION_CITY_FORUM) 
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

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_names} has/have added Renown to The Forums and may intervene this turn.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_names" => $playerNames
            ]);

            $this->notifyInterveneList($game);
        }

        //Reset the intervene list at the end of the day
        if ($event instanceof EventDuskEndOfDay)
        {
            $this->interveneList = [];
            $this->IsUpdated = true;
            $this->notifyInterveneList($event->theah->game);
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
                    throw new \BgaUserException($game->translate("Parley Gone Wrong: You cannot intervene at The Forum because you did not contribute Renown to The Forum."));
                }
            }
        }
    }
    
    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01150)
        {
            $playerName = $game->getPlayerNameById($game->getActivePlayerId());

            $location = $ids[0];
    
            $playerId = $game->getActivePlayerId();

            $batchId = $game->getNextEventBatchId();

            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent($playerId, $location, Game::LOCATION_CITY_FORUM, 1, $playerName);
            $movingEvent->batchId = $batchId;
            $movingEvent->priority = Event::HIGH_PRIORITY;
            $game->theah->eventCheck($movingEvent);

            // WHY: HIGH_PRIORITY so this opponent's remove/add fires before the next opponent's
            // queued MEDIUM_PRIORITY transition. Otherwise every opponent sees pre-resolution renown
            // and can pick the same already-depleted location, driving it negative.
            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent($playerId, $location, 1, $playerName);
            $removeEvent->priority = Event::HIGH_PRIORITY;
            $removeEvent->batchId = $batchId;
            $game->theah->eventCheck($removeEvent);

            $addEvent = EventFactory::createRenownAddedToLocationEvent($playerId, Game::LOCATION_CITY_FORUM, 1, $playerName, $isMove = true);
            $addEvent->priority = Event::HIGH_PRIORITY;
            $addEvent->batchId = $batchId;
            $game->theah->eventCheck($addEvent);

            $game->theah->queueEvent($movingEvent);
            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);
    
            $game->gamestate->nextState("");
    
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01150)
        {
            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} has passed choosing a location to remove Renown from.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $game->getActivePlayerName()
            ]);

            $game->gamestate->nextState();
        }
    }

    private function notifyInterveneList(Game $game): void
    {
        $list = [];
        foreach ($this->interveneList as $playerId) {
            $list[] = [
                'playerId' => $playerId,
                'playerName' => $game->getPlayerNameById($playerId),
                'playerColor' => $game->getPlayerColorById($playerId),
            ];
        }

        $game->notify->all("parleyInterveneListUpdated", '', [
            'interveneList' => $list,
        ]);
    }

    public function getInterveneListData(Game $game): array
    {
        $list = [];
        foreach ($this->interveneList as $playerId) {
            $list[] = [
                'playerId' => $playerId,
                'playerName' => $game->getPlayerNameById($playerId),
                'playerColor' => $game->getPlayerColorById($playerId),
            ];
        }
        return $list;
    }
}