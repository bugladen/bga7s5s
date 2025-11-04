<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01125 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("The Boar's Guile");
        $this->Image = "img/cards/7s5s/125.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 125;

        $this->Faction = "Ussura";
        $this->Initiative = 40;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Cunning", 
            "Hunt",
        ];

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. 
            ${player_name} may first choose a city location to place Renown onto. 
            If they choose not to, they may move a Renown from a city location to an adjacent location. 
            Lastly, they will choose an enemy character.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01125");
            $event->theah->queueEvent($transition);            
        }

        if ($event instanceof EventDuelCalculateCombatCardStats)
        {
            
        }

        if ($event instanceof EventDuskPhaseBegin && $this->Location == Game::LOCATION_PLAYER_HOME)
        {

            $game = $event->theah->game;
            $cards = $event->theah->getAllCards();
            $characters = array_filter($cards, fn($card) => $card instanceof Character && $card->hasCondition(Game::ADVERSARY_OF_YEVGENI));
            if (count($characters) > 0)
            {
                $character = array_values($characters)[0];
                if ($character->Location == Game::LOCATION_PLAYER_HOME)
                {
                    $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${character_inject_code} is an Adversary of Yevgeni 
                    and is at home at the beginning of Dusk.  ${player_name} gains 1 Renown.'), [
                        "scheme_inject_code" => $this->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($this->ControllerId),
                    ]);
                    
                    $renownEvent = EventFactory::createPlayerGainsReknownEvent($this->ControllerId, 1);
                    $event->theah->queueEvent($renownEvent);
                }

                $character->removeCondition(Game::ADVERSARY_OF_YEVGENI);

                $game->notify->all("yevgeniAdversaryRemoved", "", [
                    "cardId" => $character->Id,
                ]);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);
        
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_3)
        {
            $args["location"] = $game->globals->get(GAME::CHOSEN_LOCATION);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125)
        {
            $location = $ids[0];

            $playerId = $game->getActivePlayerId();
            $event = EventFactory::createReknownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->notify->player($playerId, 'message', 
                clienttranslate('Private: You have chosen to place renown onto ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
                'i18n' => ['location'],
                "location" => $location
            ]);
    
            $game->gamestate->nextState("reknownPlaced");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_2)
        {
            $location = $ids[0];
            $playerId = $game->getActivePlayerId();

            //Check if the location actually has reknown to move
            $reknown = $game->getReknownForLocation($location);
            if ($reknown <= 0) 
                throw new \BgaUserException(sprintf($game->translate("%s does not have any renown to move."), $location));
            
            $event = EventFactory::createReknownRemovedFromLocationEvent($playerId, $location, 1, "The Boar's Guile: Moving Renown from one Location to an adjacent location");
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->notify->player($playerId, 'message', 
                clienttranslate('Private: You have chosen to move renown from ${location}.  You must now choose a location to move the Renown TO.'), [
                'i18n' => ['location'],
                "location" => $location
            ]);
            
            $game->globals->set(GAME::CHOSEN_LOCATION, $location);
    
            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_3)
        {
            $location = $ids[0];

            $playerId = $game->getActivePlayerId();
            $event = EventFactory::createReknownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->notify->player($playerId, 'message', 
                clienttranslate('Private: You have chosen to move renown to ${location}.  Per The Boar\'s Guile you must now choose an enemy character to target.'), [
                'i18n' => ['location'],
                "location" => $location
            ]);
    
            $game->gamestate->nextState("");
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4)
        {
            $playerName = $game->getActivePlayerName();
            $character = $game->getCardObjectFromDb($id);
    
            $game->notify->all('yevgeniAdversaryChosen', 
                clienttranslate('${player_name} has chosen ${character_inject_code} as Yevgeni\'s Adversary.'), [
                "player_name" => $playerName,
                "character_inject_code" => $character->getInjectCode(),
                "cardId" => $character->Id,
            ]);
    
            $character->addCondition(Game::ADVERSARY_OF_YEVGENI);
            $game->updateCardObjectInDb($character);
    
            $game->gamestate->nextState("");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125)
        {
            $game->notify->all("message", 
            clienttranslate('Private: You have chosen to pass placing renown onto a location.  Per The Boar\'s Guile you will now choose a city location to move a Renown FROM.'), []);

            $game->gamestate->nextState("pass");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_2)
        {
            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($location) => $location->Reknown > 0);
            if (count($locations) > 0)
            {
                throw new \BgaUserException($game->translate("There are locations with renown to move."));
            }

            $game->notify->player($game->getActivePlayerId(), 'message', 
            clienttranslate('Private: You have passed choosing a location to move renown from.  Per The Boar\'s Guile you must now choose an enemy character to target.'), []);

            $game->gamestate->nextState("pass");
        }
        
    }
}