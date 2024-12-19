<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01145 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Inspire Generosity";
        $this->Image = "img/cards/7s5s/145.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 145;

        $this->Faction = "";
        $this->Initiative = 15;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bureaucracy", 
            "Camaraderie",
        ];
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('The first part of ${scheme_name} now resolves. ${player_name} must move a Reknown from one location to another.
            Then, Reknown will be added to all locations that have none.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            $event->theah->game->notifyAllPlayers("message", clienttranslate('The second part of ${scheme_name} will happen after. Each player draws a card.
            Then, the player with the least Reknown draw a card  Then the player with the fewest characters will draw a card.'), [
                "scheme_name" => "<span style='font-weight:bold'>Inspire Generosity</span>",
            ]);
    
            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01145';
            }
            $event->theah->queueEvent($transition);
        }
    }

    public function planningPhaseAction($game, $fromLocation, $toLocation)
    {
        $playerId = $game->getActivePlayerId();
        $playerName = $game->getActivePlayerName();
        $players = $game->loadPlayersBasicInfos();
        $playerCount = count($players);

        if ($fromLocation == 'Pass' || $toLocation == 'Pass')
        {
            $game->notifyAllPlayers('message', 
                clienttranslate('${player_name} has chosen to pass on moving a reknown.'), [
                "player_name" => $playerName,
            ]);
        }
        else
        {
            if ($game->getReknownForLocation($fromLocation) == 0)
            {
                throw new \BgaUserException("{$fromLocation} does not have any reknown to move.");
            }
   
            $playerAdded = $game->theah->createEvent(Events::ReknownRemovedFromLocation);
            if ($playerAdded instanceof EventReknownRemovedFromLocation) {
                $playerAdded->playerId = $playerId;
                $playerAdded->location = $fromLocation;
                $playerAdded->amount = 1;
                $playerAdded->source = $playerName;
            }
            $game->theah->eventCheck($playerAdded);
            $game->theah->queueEvent($playerAdded);
    
            $playerAdded = $game->theah->createEvent(Events::ReknownAddedToLocation);
            if ($playerAdded instanceof EventReknownAddedToLocation) {
                $playerAdded->playerId = $playerId;
                $playerAdded->location = $toLocation;
                $playerAdded->amount = 1;
                $playerAdded->source = $playerName;
            }
            $game->theah->eventCheck($playerAdded);
            $game->theah->queueEvent($playerAdded);
        }

        $scheme = $game->getPlayerChosenScheme($playerId);

        //Place Reknown at locations that have none
        $amount = $game->getReknownForLocation(Game::LOCATION_CITY_DOCKS);
        if ($toLocation == Game::LOCATION_CITY_DOCKS) {
            $amount++;
        }
        if ($fromLocation == Game::LOCATION_CITY_DOCKS) {
            $amount--;
        }
        if ($amount == 0)
        {
            $docksAdded = $game->theah->createEvent(Events::ReknownAddedToLocation);
            if ($docksAdded instanceof EventReknownAddedToLocation) {
                $docksAdded->playerId = $playerId;
                $docksAdded->location = Game::LOCATION_CITY_DOCKS;
                $docksAdded->amount = 1;
                $docksAdded->source = "{$scheme->Name} - location had no reknown";
            }
            // No pre-event check needed - not a player choice
            $game->theah->queueEvent($docksAdded);
        }

        $amount = $game->getReknownForLocation(Game::LOCATION_CITY_FORUM);
        if ($toLocation == Game::LOCATION_CITY_FORUM) {
            $amount++;
        }
        if ($fromLocation == Game::LOCATION_CITY_FORUM) {
            $amount--;
        }
        if ($amount == 0)
        {
            $forumAdded = $game->theah->createEvent(Events::ReknownAddedToLocation);
            if ($forumAdded instanceof EventReknownAddedToLocation) {
                $forumAdded->playerId = $playerId;
                $forumAdded->location = Game::LOCATION_CITY_FORUM;
                $forumAdded->amount = 1;
                $forumAdded->source = "{$scheme->Name} - location had no reknown";
            }
            // No pre-event check needed - not a player choice
            $game->theah->queueEvent($forumAdded);
        }

        $amount = $game->getReknownForLocation(Game::LOCATION_CITY_BAZAAR);
        if ($toLocation == Game::LOCATION_CITY_BAZAAR) {
            $amount++;
        }
        if ($fromLocation == Game::LOCATION_CITY_BAZAAR) {
            $amount--;
        }
        if ($amount == 0)
        {
            $forumAdded = $game->theah->createEvent(Events::ReknownAddedToLocation);
            if ($forumAdded instanceof EventReknownAddedToLocation) {
                $forumAdded->playerId = $playerId;
                $forumAdded->location = Game::LOCATION_CITY_BAZAAR;
                $forumAdded->amount = 1;
                $forumAdded->source = "{$scheme->Name} - location had no reknown";
            }
            // No pre-event check needed - not a player choice
            $game->theah->queueEvent($forumAdded);
        }

        $amount = $game->getReknownForLocation(Game::LOCATION_CITY_OLES_INN);
        if ($toLocation == Game::LOCATION_CITY_OLES_INN) {
            $amount++;
        }
        if ($fromLocation == Game::LOCATION_CITY_OLES_INN) {
            $amount--;
        }
        if ($playerCount > 2 && $amount == 0)
        {
            $forumAdded = $game->theah->createEvent(Events::ReknownAddedToLocation);
            if ($forumAdded instanceof EventReknownAddedToLocation) {
                $forumAdded->playerId = $playerId;
                $forumAdded->location = Game::LOCATION_CITY_OLES_INN;
                $forumAdded->amount = 1;
                $forumAdded->source = "{$scheme->Name} - location had no reknown";
            }
            // No pre-event check needed - not a player choice
            $game->theah->queueEvent($forumAdded);
        }

        $amount = $game->getReknownForLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
        if ($toLocation == Game::LOCATION_CITY_GOVERNORS_GARDEN) {
            $amount++;
        }
        if ($fromLocation == Game::LOCATION_CITY_GOVERNORS_GARDEN) {
            $amount--;
        }
        if ($playerCount > 3 && $amount == 0)
        {
            $forumAdded = $game->theah->createEvent(Events::ReknownAddedToLocation);
            if ($forumAdded instanceof EventReknownAddedToLocation) {
                $forumAdded->playerId = $playerId;
                $forumAdded->location = Game::LOCATION_CITY_GOVERNORS_GARDEN;
                $forumAdded->amount = 1;
                $forumAdded->source = "{$scheme->Name} - location had no reknown";
            }
            // No pre-event check needed - not a player choice
            $game->theah->queueEvent($forumAdded);
        }

        //Each player will now draw a card
        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player) {
            $card = $game->playerDrawCard($playerId);
            $addEvent = $game->theah->createEvent(Events::CardDrawn);
            if ($addEvent instanceof EventCardDrawn) {
                $addEvent->card = $card;
                $addEvent->playerId = $playerId;
                $addEvent->reason = "<strong>Inspire Generosity</strong> effect";
            }
            //No need for a check
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
            $card = $game->playerDrawCard($lowestPlayer);
            $addEvent = $game->theah->createEvent(Events::CardDrawn);
            if ($addEvent instanceof EventCardDrawn) {
                $addEvent->card = $card;
                $addEvent->playerId = $lowestPlayer;
                $addEvent->reason = "<strong>Inspire Generosity</strong> effect - player has fewest reknown";
            }
            //No need for a check
            $game->theah->queueEvent($addEvent);
        }
        
        //Lastly, the player with the fewest characters will draw a card
        list($lowestPlayer, $lowestCount) = $game->getPlayerControllingFewestCharacters();

        if ($lowestPlayer != null && $lowestPlayer == $this->ControllerId)
        {
            $card = $game->playerDrawCard($lowestPlayer);
            $addEvent = $game->theah->createEvent(Events::CardDrawn);
            if ($addEvent instanceof EventCardDrawn) {
                $addEvent->card = $card;
                $addEvent->playerId = $lowestPlayer;
                $addEvent->reason = "<strong>Inspire Generosity</strong> effect - player has fewest characters in play";
            }
            //No need for a check
            $game->theah->queueEvent($addEvent);
        }
    }
}