<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01145 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Inspire Generosity");
        $this->Image = "01145.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 145;

        $this->Initiative = 15;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Bureaucracy"), 
            clienttranslate("Camaraderie"),
        ];

        $this->Text = clienttranslate("<p>Move a Renown from a location to another location. Then, add a Renown to each location that has none.</p><p>Each player draws a card. Then, the player with the least Renown draws a card. Then, the player with the fewest characters draws a card. (Least and fewest cannot tie.)</p>");

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notify->all("message", clienttranslate('The first part of ${scheme_inject_code} now resolves. ${player_name} must move a Renown from one location to another.
            Then, Renown will be added to all locations that have none.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $event->theah->game->notify->all("message", clienttranslate('The second part of ${scheme_inject_code} will happen after. Each player draws a card.
            Then, the player with the least Renown draw a card  Then the player with the fewest characters will draw a card.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);
    
            //Transition to the state where player can choose two locations.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, '01145');
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);
        
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2)
        {
            $args["chosenLocation"] = $game->globals->get(Game::CHOSEN_LOCATION);
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01145)
        {
            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($location) => $location->Reknown > 0);
            if (count($locations) > 0)
            {
                throw new \BgaUserException($game->translate("There are locations with Renown to move."));
            }

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01145)
        {
            $fromLocation = $ids[0];

            if ($game->getReknownForLocation($fromLocation) == 0)
            {
                throw new \BgaUserException(sprintf($game->translate("%s does not have any Renown to move."), $fromLocation));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $fromLocation);

            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_2)
        {
            $fromLocation = $game->globals->get(Game::CHOSEN_LOCATION);
            $toLocation = $ids[0];

            $playerId = $game->getActivePlayerId();
    
            $playerRemoved = EventFactory::createReknownRemovedFromLocationEvent($playerId, $fromLocation, 1, $this->getInjectCode());
            $game->theah->eventCheck($playerRemoved);
            $game->theah->queueEvent($playerRemoved);
    
            $playerAdded = EventFactory::createReknownAddedToLocationEvent($playerId, $toLocation, 1, $this->getInjectCode(), $isMove = true);
            $game->theah->eventCheck($playerAdded);
            $game->theah->queueEvent($playerAdded);

            $game->globals->set(Game::CHOSEN_CARD, $toLocation);

            $game->gamestate->nextState("locationChosen");
        }
    }

    public function stateFromCard(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::stateFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01145_3)
        {
            $toLocation = $game->globals->get(Game::CHOSEN_CARD);
            $fromLocation = $game->globals->get(Game::CHOSEN_LOCATION);

            $playerId = $game->getActivePlayerId();

            //Place Reknown at locations that have none
            $locations = $game->theah->getCityLocations();
            foreach ($locations as $location)
            {
                $amount = $location->Reknown;
                if ($toLocation == $location->Name) {
                    $amount++;
                }
                if ($fromLocation == $location->Name) {
                    $amount--;
                }

                if ($amount == 0)
                {
                    $reknownEvent = EventFactory::createReknownAddedToLocationEvent($playerId, $location->Name, 1, $this->getInjectCode());
                    $game->theah->queueEvent($reknownEvent);
                }
            }

            //Each player will now draw a card
            $players = $game->loadPlayersBasicInfos();
            foreach ($players as $playerId => $player) {
                $addEvent = EventFactory::createCardDrawnEvent($playerId, sprintf($game->translate("%s effect"), $this->getInjectCode()));
                $game->theah->queueEvent($addEvent);
            }

            //Now the player with the least amount of reknown will draw a card
            // Get all the reknown to compare
            $db = $game->theah->getDBObject();
            $lowestPlayer = 0;
            $players = $db->getObjectList("SELECT player_id, player_score score FROM player ORDER BY player_score");
            $firstPlayer = $players[0]['player_id'];  
            if (count($players) == 1) {
                $lowestPlayer = $firstPlayer;
            }
            else
            {
                $lowest = $players[0]['score'];
                $secondLowest = $players[1]['score'];
                if ($lowest != $secondLowest) {
                    $lowestPlayer = $players[0]['player_id'];
                }    
            }

            if ($lowestPlayer != 0)
            {
                $addEvent = EventFactory::createCardDrawnEvent($lowestPlayer, sprintf($game->translate("%s effect - player has fewest Renown"), $this->getInjectCode()));   
                //No need for a check
                $game->theah->queueEvent($addEvent);
            }
            
            //Lastly, the player with the fewest characters will draw a card
            [$lowestPlayer, $lowestCount] = $game->getPlayerControllingFewestCharacters();

            if ($lowestPlayer != null)
            {
                $addEvent = EventFactory::createCardDrawnEvent($lowestPlayer, sprintf($game->translate("%s effect - player has fewest characters in play"), $this->getInjectCode()));
                //No need for a check
                $game->theah->queueEvent($addEvent);
            }

            $game->gamestate->nextState();
        }
    }
}