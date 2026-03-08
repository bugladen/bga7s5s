<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01178;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions\Reaction_CrewCapLimit;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\actions\BasicChallengeAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\actions\GovernorsGardenAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\actions\OlesInnAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChangeActivePlayer;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Theah
{
    public Game $game;
    private array $cards;
    private array $cityLocations;
    private DB $db;
    private bool $cityBuilt;

    private Array $Reactions;
    private Array $Actions;

    use EventHub;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
        $this->cityLocations = [];
        $this->db = new DB($game);
        $this->cityBuilt = false;
        $this->Actions = [];
        $this->Reactions = [];
    }

    public function addCardToWorld(Card $card)
    {
        $this->cards[$card->Id] = $card;
    }

    public function argsFromReaction(int $state, string $stateName, string $internalId): array 
    {
        $args = [];

        $reaction = $this->getTheahReactionById($internalId);
        $args['buttons'] = $reaction->getReactionButtonProperties($this);
        $args['descriptionmyturn'] = $reaction->getReactionDescription($this);

        return $args; 
    }


    public function getDBObject()
    {
        return $this->db;
    }

    public function buildCity()
    {
        if ($this->cityBuilt) return;

        $this->cards = [];

        if ($this->game->getPlayerCount() >= 3) 
        {
            $playersThatUsedOlesInn = $this->game->globals->get(Game::PLAYERS_THAT_USED_OLES_INN, []);
            $this->Actions[] = new OlesInnAction($playersThatUsedOlesInn, $this->game);
        }

        if ($this->game->getPlayerCount() >= 4) 
        {   
            $playersThatUsedGovernorsGarden = $this->game->globals->get(Game::PLAYERS_THAT_USED_GOVERNORS_GARDEN, []);
            $this->Actions[] = new GovernorsGardenAction($playersThatUsedGovernorsGarden, $this->game);
        }

        $this->Actions[] = new BasicChallengeAction();

        $this->Reactions = [
            new Reaction_CrewCapLimit(),
        ];

        $this->buildCityLocations();

        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PLAYER_HOME);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_OLES_INN);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_FORUM);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PURGATORY);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_HAND);

        $playerIds = $this->db->getPlayerIds();
        foreach ($playerIds as $playerId) 
        {
            $discardDeckName = $this->game->getPlayerDiscardDeckName($playerId["id"]);
            $this->cards += $this->db->getCardObjectsAtLocation($discardDeckName);
        }

        $this->cityBuilt = true;
    }

    private function buildCityLocations()
    {
        $players = $this->game->loadPlayersBasicInfos();
        $game = $this->game;

        $location = new CityLocation(Game::LOCATION_CITY_DOCKS);
        $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_DOCKS);
        $location->Controller = $game->getControllerForLocation(Game::LOCATION_CITY_DOCKS);
        $this->cityLocations[Game::LOCATION_CITY_DOCKS] = $location;

        $location = new CityLocation(Game::LOCATION_CITY_FORUM);
        $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_FORUM);
        $location->Controller = $game->getControllerForLocation(Game::LOCATION_CITY_FORUM);
        $this->cityLocations[Game::LOCATION_CITY_FORUM] = $location;

        $location = new CityLocation(Game::LOCATION_CITY_BAZAAR);
        $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_BAZAAR);
        $location->Controller = $game->getControllerForLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cityLocations[Game::LOCATION_CITY_BAZAAR] = $location;

        if (count($players) > 2) {
            $location = new CityLocation(Game::LOCATION_CITY_OLES_INN);
            $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_OLES_INN);
            $location->Controller = $game->getControllerForLocation(Game::LOCATION_CITY_OLES_INN);
            $this->cityLocations[Game::LOCATION_CITY_OLES_INN] = $location;
        }

        if (count($players) > 3) {
            $location = new CityLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
            $location->Reknown = $game->getReknownForLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
            $location->Controller = $game->getControllerForLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
            $this->cityLocations[Game::LOCATION_CITY_GOVERNORS_GARDEN] = $location;
        }
    }

    public function createEvent(string $eventName) : Event
    {
        $className = "\Bga\Games\SeventhSeaCityOfFiveSails\\theah\\events\\$eventName";
        $event = new $className();
        return $event;
    }

    /// <summary>
    /// Run through the cards in the city to see if an event can be run.
    /// A card should throw an exception if it cannot allow the event.
    ///
    /// Call this directly only when you need to queue up several events in one method.
    /// In that case call this for each event before queueing them up.
    /// See examples in FrameworkActionsTrait.php
    ///
    /// Otherwise, if you have only one event, call queueEvent() to queue up the event intead, 
    /// which will call this method.
    /// </summary>
    public function eventCheck(Event $event)
    {
        $this->buildCity();
        foreach ($this->cards as $card) {
            $event->theah = $this;
            $card->eventCheck($event);
            unset($event->theah);
        }
    }

    public function queueEvent(Event $event)
    {    
        $this->buildCity();
        try {
            $this->eventCheck($event);
            $this->db->queueEvent($event);
        } catch (\Exception $e) {
            $this->game->notify->all("message", clienttranslate($e->getMessage()), []);
        }
    }

    public function stackEvent(Event $event)
    {
        $this->buildCity();
        try {
            $this->eventCheck($event);
            $this->db->stackEvent($event);
        } catch (\Exception $e) {
            $this->game->notify->all("message", clienttranslate($e->getMessage()), []);
        }
    }

    public function runEvents(bool $debug = false)
    {
        while (true) {
           
            // Get the next event from the database
            $event = $this->db->getNextEvent();

            // Break if there are no more events
            if (!$event) break;

            $event->theah = $this;

            if ( ! $event->runEventHubAfterCards)
                $this->handleEvent($event);

            //Run the event for all cards in play, including hands
            foreach ($this->cards as $card) 
                $card->handleEvent($event);

            //Run the event for theah actions
            foreach ($this->Actions as $action) 
                $action->handleEvent($event);

            //Run the event for theah reactions
            foreach ($this->Reactions as $reaction) 
                $reaction->handleEvent($event);
            
            // Run the event handler for Theah for cleanup
            if ($event->runEventHubAfterCards)
                $this->handleEvent($event);

            foreach ($this->cards as $card) {
            // If any cards were updated, update them in the database
                if ($card->IsUpdated) {
                    $card->IsUpdated = false;
                    $this->db->updateCardObject($card);
                }
            }

            if (! $debug && $event instanceof EventChangeActivePlayer) {
                $this->game->gamestate->changeActivePlayer($event->playerId);
            }

            if (! $debug && $event instanceof EventTransition) {              
                
                //If a reaction transition, make sure it is available.  
                //This prevents multiple transition triggers of the same reaction from running.
                if ($event->transition == "reaction") 
                {
                    $card = $this->getCardById($event->sourceId);
                    if ($card && $card instanceof IHasReactions)
                    {
                        $reaction = $card->getReactionById($event->internalId);
                        if ($reaction && ! $reaction->isAvailable())
                        {
                            continue;
                        }
                    }
                }

                if ($event->getPlayerId()) 
                {
                    $this->game->gamestate->changeActivePlayer($event->getPlayerId());
                }

                $this->game->globals->set(Game::TRANSITION_SOURCE_ID, $event->sourceId);
                $this->game->globals->set(Game::TRANSITION_INTERNAL_ID, $event->internalId);

                $this->game->gamestate->nextState($event->transition);
                return;
            }
        }

        //After all the events are run, we need to reset the wounds healed incoming for all characters
        foreach ($this->cards as $card)
        {
            if ($card instanceof Character)
            {
                $card->WoundsHealedIncoming = 0;
            }
        }

        //After all the events are run, we need to change back to the current player
        $inDuel = $this->game->globals->get(Game::IN_DUEL);
        if ($inDuel) 
        {
            $currentPlayerId = $this->game->globals->get(Game::DUEL_CURRENT_PLAYER);
            if ($currentPlayerId && ! $debug) 
            {
                $this->game->gamestate->changeActivePlayer($currentPlayerId);
            }
        }
        else 
        {
            $state = $this->game->gamestate->getCurrentMainState();
            if ($state['type'] == "activeplayer")
            {
                $currentPlayerId = $this->game->globals->get(Game::CURRENT_PLAYER);
                if ($currentPlayerId && ! $debug) 
                {
                    $this->game->gamestate->changeActivePlayer($currentPlayerId);
                }
            }
        }

        if (! $debug) {
            $this->game->gamestate->nextState('endOfEvents');
        }
    }

    function getAdjacentCityLocations(string $location, bool $includeHome = true): array
    {
        $playerCount = $this->game->globals->get(Game::PLAYER_COUNT);
        $locations = [];
        switch ($location) {
            case Game::LOCATION_PLAYER_HOME:
                $locations = [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR];
                if ($playerCount > 2) {
                    $locations[] = Game::LOCATION_CITY_OLES_INN;
                }
                if ($playerCount > 3) {
                    $locations[] = Game::LOCATION_CITY_GOVERNORS_GARDEN;
                }
                break;

            case Game::LOCATION_CITY_DOCKS:
                $locations = [Game::LOCATION_CITY_FORUM];
                if ($playerCount > 2) {
                    $locations[] = Game::LOCATION_CITY_OLES_INN;
                }
                break;

            case Game::LOCATION_CITY_FORUM:
                $locations = [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR];
                break;

            case Game::LOCATION_CITY_BAZAAR:
                $locations = [Game::LOCATION_CITY_FORUM];
                if ($playerCount > 3) {
                    $locations[] = Game::LOCATION_CITY_GOVERNORS_GARDEN;
                }
                break;

            case Game::LOCATION_CITY_OLES_INN:
                $locations = [Game::LOCATION_CITY_DOCKS];
                break;

            case Game::LOCATION_CITY_GOVERNORS_GARDEN:
                $locations = [Game::LOCATION_CITY_BAZAAR];
                break;
        }

        if ($includeHome && $location != Game::LOCATION_PLAYER_HOME) {
            $locations[] = Game::LOCATION_PLAYER_HOME;
        }

        return $locations;
    }

    function getOuterCityLocations(): array
    {
        $playerCount = $this->game->getPlayerCount();
        switch ($playerCount) {
            case 1:
            case 2:
                return [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR];
            case 3:
                return [Game::LOCATION_CITY_OLES_INN, Game::LOCATION_CITY_BAZAAR];
            case 4:
                return [Game::LOCATION_CITY_OLES_INN, Game::LOCATION_CITY_GOVERNORS_GARDEN];
        }
        return [];
    }

    function getNonAdjacentCityLocations(string $location): array
    {
        $locations = $this->getCityLocations();
        $locations = array_filter($locations, fn($loc) => $loc->Name != $location);
        $adjacentLocations = $this->getAdjacentCityLocations($location);
        
        foreach ($adjacentLocations as $adjacentLocation)
        {
            $locations = array_filter($locations, fn($loc) => $loc->Name != $adjacentLocation);
        }

        return array_map(fn($loc) => $loc->Name, array_values($locations));
    }

    function getActionFromHandDiscount(?Character $performer, CardAction $action): Array
    {
        $cards = $this->cards;
        $discount = 0;
        $explanations = [];
        foreach ($cards as $card) {
            $discount += $card->getActionFromHandDiscount($this, $performer, $action, $explanations);
        }

        $explanations = implode("<br>", $explanations);
            
        return [$discount, $explanations];
    }

    function getManeuverFromCombatCardDiscount(Card $combatCard): Array
    {
        $cards = $this->cards;
        $discount = 0;
        $explanations = [];
        foreach ($cards as $card) {
            $discount += $card->getManeuverFromCombatCardDiscount($this, $combatCard, $explanations);
        }

        $explanations = implode("<br>", $explanations);
        return [$discount, $explanations];
    }

    function getAvailableAttachmentsAtLocation($location)
    {
        $attachments = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Attachment && $card->Location == $location && ! $card->isAttached()) {
                $attachments[] = $card;
            }
        }
        return $attachments;
    }

    function getAvailableCharacterTechniques($character)
    {
        $techniques = [];

        if ($character instanceof IHasTechniques) 
            $techniques = array_merge($techniques, $character->getTechniquesArray($this->game, $mustBeAvailable = true));

        foreach($character->Attachments as $attachment) 
        {
            $attachmentCard = $this->getCardById($attachment);
            if ($attachmentCard instanceof IHasTechniques) {
                $techniques = array_merge($techniques, $attachmentCard->getTechniquesArray($this->game, $mustBeAvailable = true));
            }
        }

        return $techniques;
    }

    function getAvailableCharacterManeuvers($character)
    {
        $maneuvers = [];

        if ( ! $character instanceof IHasManeuvers) {
            return $maneuvers;
        }

        $maneuvers += $character->getManeuversArray($this->game, $mustBeAvailable = true);

        foreach($character->Attachments as $attachment) {
            $attachmentCard = $this->getCardById($attachment);
            if ($attachmentCard instanceof IHasManeuvers) {
                $maneuvers += $attachmentCard->getManeuversArray($this->game, $mustBeAvailable = true);
            }
        }

        return $maneuvers;
    }

    public function getAllCards(): array
    {
        return $this->cards;
    }

    public function getCardsInPlay(): array
    {
        $cards = array_filter($this->cards, fn($card) => $card->isControlled() && ($this->cardInCity($card) || $card->Location == Game::LOCATION_PLAYER_HOME));
        
        return $cards;
    }

    public function getCardPropertiesAtLocation($location, $playerId = null)
    {
        $cards = [];
        foreach ($this->cards as $card) {
            if ($card->Location == $location) {
                if ($playerId !== null && $card->ControllerId != $playerId) {
                    continue;
                }
                $cards[] = $card->getPropertyArray($this->game);
                unset($card);
            }
        }
        return $cards;
    }

    public function getCardObjectsAtLocation($location, $playerId = null): array
    {
        return $this->db->getCardObjectsAtLocation($location, $playerId);
    }

    public function getCardById($cardId) : ?Card
    {
        if ($cardId == 0) {
            return null;
        }

        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        $card = $this->db->getCardObject($cardId);
        if ($card) {
            return $card;
        }

        return null;
    }

    public function getCardByName($name) : ?Card
    {
        foreach ($this->cards as $card) {
            if ($card->Name == $name) {
                return $card;
            }
        }

        return null;
    }

    public function getCardByType($type) : ?Card
    {
        foreach ($this->cards as $card) {
            if ($card instanceof $type) {
                return $card;
            }
        }

        return null;
    }

    public function getCityLocation(string $name): CityLocation
    {
        if ( ! array_key_exists($name, $this->cityLocations))
        {
            throw new \Exception("City location $name not found");
        }
        
        return $this->cityLocations[$name];
    }

    public function getCityLocations(): array
    {
        return $this->cityLocations;
    }

    public function getCityLocationReknown()
    {
        $reknown = [];
        foreach ($this->cityLocations as $location) {
            $reknown[$location->Name] = $location->Reknown;
        }
        return $reknown;
    }

    public function getCityLocationControllers()
    {
        $controllers = [];
        foreach ($this->cityLocations as $location) {
            $controllers[$location->Name] = $location->Controller;
        }
        return $controllers;
    }

    function getCharacterCountByPlayerId(int $playerId, bool $includeBrutes = false): int
    {
        $count = 0;
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $this->cardInPlay($card) && $card->ControllerId == $playerId && (! $card->hasTrait("Brute") || $includeBrutes)) {
                $count++;
            }
        }
        return $count;
    }

    function getCharacterById($id): ?Character
    {
        $character = $this->getCardById($id);
        if ($character instanceof Character) {
            return $character;
        }

        return null;
    }

    function getAttachmentById($id): ?Attachment
    {
        $attachment = $this->getCardById($id);
        if ($attachment instanceof Attachment) {
            return $attachment;
        }

        return null;
    }

    function getRiskById($id): ?Risk
    {
        $risk = $this->getCardById($id);
        if ($risk instanceof Risk) {
            return $risk;
        }

        return null;
    }

    function getSchemeById($id): ?Scheme
    {
        $scheme = $this->getCardById($id);
        if ($scheme instanceof Scheme) {
            return $scheme;
        }

        return null;
    }

    function getCharactersInPlay(): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->isControlled() && ($this->cardInCity($card) || $card->Location == Game::LOCATION_PLAYER_HOME)) {
                $characters[] = $card;
            }
        }
        return $characters;
    }   

    
    function getCharactersInPlayByPlayerId($playerId): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->ControllerId == $playerId && ($this->cardInCity($card) || $card->Location == Game::LOCATION_PLAYER_HOME)) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersInCityByPlayerId($playerId): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->ControllerId == $playerId && $this->cardInCity($card)) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersAtHome(): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->Location == Game::LOCATION_PLAYER_HOME) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersAtHomeByPlayerId(int $playerId): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->Location == Game::LOCATION_PLAYER_HOME && $card->ControllerId == $playerId) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersAtLocation(string $location, bool $includeUncontrolled = false)
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && ($card->isControlled() || $includeUncontrolled) && $card->Location == $location) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false)
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->Location == $location && $card->ControllerId == $playerId) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getEquipDiscount(Character $performer, Attachment $attachment): Array
    {
        //Smuggled Item cost is free
        if ($this->game->globals->get(Game::EQUIP_TYPE) == Game::SMUGGLED_ITEM_EQUIP_TYPE) {
            return [$attachment->WealthCost, $this->game->translate("Smuggled Item: Cost is free")];
        }
        
        $discount = 0;
        $explanations = [];
        foreach ($this->cards as $card) {
            $discount += $card->getEquipDiscount($this, $performer, $attachment, $explanations);
        }
        $explanations = implode("<br>", $explanations);
        return [$discount, $explanations];
    }

    function getParleyDiscount(Character $performer, bool $parleying): Array
    {
        $discount = 0;
        $explanations = [];
        foreach ($this->cards as $card) {
            $discount += $card->getParleyDiscount($this,$performer, $parleying, $explanations);
        }

        $explanations = implode("<br>", $explanations);
        return [$discount, $explanations];
    }

    function getPlayBruteDiscount(Character $brute): int
    {
        $discount = 0;
        foreach ($this->cards as $card) 
        {
            $discount += $card->getPlayBruteDiscount($this, $brute);
        }

        return $discount;
    }

    function getInPlayActionsAvailableToPlayer($playerId)
    {
        $actionsArray = [];

        foreach ($this->Actions as $action)
        {
            if ($action->isAvailableToPlayer($playerId, $this))
            {
                $actionsArray[] = $action->getPropertyArray($this->game);
            }
        }

        foreach ($this->cards as $card)
        {
            if ($card instanceof IHasActions && $card->Location != Game::LOCATION_HAND)
            {
                $actions = $card->getActionsArray($this->game);
                foreach ($actions as $actionItem)
                {
                    $action = $card->getActionById($actionItem['id']);
                    if ($action->isAvailableToPlayer($playerId, $this))
                    {
                        $actionsArray[] = $actionItem;
                    }
                }
            }
        }

        return $actionsArray;
    }

    function getInHandActionIdsAvailableToPlayer($playerId): array
    {
        $actionsArray = [];
        $cards = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        foreach ($cards as $card)
        {
            if ($card instanceof IHasActions)
            {
                $actions = $card->getActionsArray($this->game);
                foreach ($actions as $actionItem)
                {
                    $action = $card->getActionById($actionItem['id']);
                    if ($action->isAvailableToPlayer($playerId, $this))
                    {
                        $actionsArray[] = $actionItem;
                    }
                }
            }
        }

        return $actionsArray;
    }

    function getInHandActionCardIdsAvailableToPlayer($playerId): array
    {
        $actionsArray = [];
        $cards = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        foreach ($cards as $card)
        {
            if ($card instanceof IHasActions)
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    if ($action->isAvailableToPlayer($playerId, $this))
                    {
                        $actionsArray[] = $card->Id;
                    }
                }
            }
        }

        //Filter out any duplicates
        $actionsArray = array_unique($actionsArray, SORT_REGULAR);

        return array_values($actionsArray);
    }

    function getBrutesAvailableToPlayer($playerId): array
    {
        $brutes = [];
        $cards = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        foreach ($cards as $card)
        {
            if ($card instanceof Character && $card->hasTrait("Brute"))
            {
                $brutes[] = $card->Id;
            }
        }

        return $brutes;
    }


    function getLeaderByPlayerId($playerId)
    {
        foreach ($this->cards as $card) {
            if ($card->ControllerId == $playerId && $card instanceof Leader) {
                return $card;
            }
        }
        return null;
    }

    function getInPlayActionById($id): ?Action
    {
        foreach ($this->Actions as $action)
        {
            if ($action->Id == $id)
            {
                return $action;
            }
        }

        foreach ($this->cards as $card) 
        {
            if ($card instanceof IHasActions) {
                $action = $card->getActionById($id);
                if ($action) {
                    return $action;
                }
            }
            
            if ($card instanceof Character) 
                foreach ($card->Attachments as $attachmentId) 
                {
                    $attachment = $this->getCardById($attachmentId);
                    if ($attachment instanceof IHasActions)
                    {
                        $action = $attachment->getActionById($id);
                        if ($action)
                            return $action;
                    }
                }
        }
        return null;
    }

    function getInHandActionById($id): ?CardAction
    {
        $cards = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->game->getActivePlayerId());
        foreach ($cards as $card) {
            if ($card instanceof IHasActions) {
                $action = $card->getActionById($id);
                if ($action) {
                    return $action;
                }
            }
        }
        return null;
    }

    public function getCharactersInCityWithOpposingCharacters(int $playerId): array
    {
        $characters = $this->getCharactersInCityByPlayerId($playerId);
        $performers = [];
        foreach ($characters as $character)
        {
            $opposingCharacters = $this->getCharactersAtLocation($character->Location);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId));
            if (count($opposingCharacters) > 0)
            {
                $performers[] = $character;
            }
        }

        return $performers;
    }

    public function getCharactersInCityWithOpposingMercenaries(int $playerId): array
    {
        $characters = $this->getCharactersInCityByPlayerId($playerId);
        $performers = [];
        foreach ($characters as $character)
        {
            $opposingCharacters = $this->getCharactersAtLocation($character->Location);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId) && $character->hasTrait("Mercenary"));
            if (count($opposingCharacters) > 0)
            {
                $performers[] = $character;
            }
        }

        return $performers;
    }

    public function getNumberOfGambleCardsToReveal(Character $actor): Array
    {
        $count = 2;
        $explanations = [];
        foreach ($this->cards as $card) {
            $count += $card->getNumberOfGambleCardsToReveal($this, $actor, $explanations);
        }
        $count = $count < 0 ? 0 : $count;
        $explanations = implode("<br>", $explanations);
            
        return [$count, $explanations];

    }

    public function getOpposingCharactersAtLocation(string $location, int $playerId): array
    {
        $characters = $this->getCharactersAtLocation($location);
        $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($playerId)));
        return $characters;
    }

    public function getOpposingMercenariesAtLocation(string $location, int $playerId): array
    {
        $characters = $this->getCharactersAtLocation($location);
        $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($playerId) && $character->hasTrait("Mercenary")));
        return $characters;
    }

    function getPressureStats(?Character $performer, string $location, string $startingStatType): Array
    {        
        $pressureTypes = [$startingStatType];
        $cardsInPlay = array_filter($this->cards, fn($card) => $this->cardInCity($card) || $card->Location == Game::LOCATION_PLAYER_HOME);
        foreach ($cardsInPlay as $card) {
            $card->getPressureStats($this, $performer, $location, $pressureTypes);
        }
        $pressureTypes = array_unique($pressureTypes);
        return array_values($pressureTypes);
    }

    function getReactionFromHandDiscount(CardReaction $reaction): Array
    {
        $discount = 0;
        $explanations = [];

        foreach ($this->cards as $card) {
            $discount += $card->getReactionFromHandDiscount($this, $reaction, $explanations);
        }

        $explanations = implode("<br>", $explanations);
        return [$discount, $explanations];
    }

    function getTechniqueById($id): ?Technique
    {
        foreach ($this->cards as $card) {

            if ($card instanceof IHasTechniques)
            {
                $technique = $card->getTechniqueById($id);
                if ($technique)
                    return $technique;
            }

            if ($card instanceof Character) 
                foreach ($card->Attachments as $attachmentId) 
                {
                    $attachment = $this->getCardById($attachmentId);
                    if ($attachment instanceof IHasTechniques)
                    {
                        $technique = $attachment->getTechniqueById($id);
                        if ($technique)
                            return $technique;
                    }
                }
        }

        return null;
    }

    public function getTheahReactionById($id): ?Reaction
    {
        foreach ($this->Reactions as $reaction)
        {
            if ($reaction->Id == $id)
            {
                return $reaction;
            }
        }
        
        return null;
    }

    function getTotalPlayerInfluence($playerId): int
    {
        $influence = 0;
        $characters = $this->getCharactersInPlayByPlayerId($playerId);
        foreach ($characters as $character) {
            $influence += $character->ModifiedInfluence;
        }
        return $influence;
    }

    function isTechniqueOwnedByCharacter($technique, $character): bool
    {
        if ($character instanceof IHasTechniques)
        {
            if ($character->getTechniqueById($technique->Id))
                return true;
        }

        foreach ($character->Attachments as $attachmentId) 
        {
            $attachment = $this->getCardById($attachmentId);
            if ($attachment instanceof IHasTechniques)
            {
                if ($attachment->getTechniqueById($technique->Id))
                    return true;
            }
        }

        return false;
    }

    function getManeuverById($id): ?Maneuver
    {
        foreach ($this->cards as $card) {

            if ($card instanceof IHasManeuvers)
            {
                $maneuver = $card->getManeuverById($id);
                if ($maneuver)
                    return $maneuver;
            }

            if ($card instanceof Character) 
                foreach ($card->Attachments as $attachmentId) 
                {
                    $attachment = $this->getCardById($attachmentId);
                    if ($attachment instanceof IHasManeuvers)
                    {
                        $maneuver = $attachment->getManeuverById($id);
                        if ($maneuver)
                            return $maneuver;
                    }
                }
        }

        return null;
    }

    function isManeuverOwnedByCharacter(Maneuver $maenuver, Character $character): bool
    {
        if ($character instanceof IHasManeuvers)
        {
            if ($character->getManeuverById($maenuver->Id))
                return true;
        }

        foreach ($character->Attachments as $attachmentId) 
        {
            $attachment = $this->getCardById($attachmentId);
            if ($attachment instanceof IHasManeuvers)
            {
                if ($attachment->getManeuverById($maenuver->Id))
                    return true;
            }
        }

        return false;
    }

    function cardInPlay(Card $card): bool
    {
        return $this->cardInCity($card) || $card->Location == Game::LOCATION_PLAYER_HOME;
    }

    function cardInCity(Card $card): bool
    {
        return $this->locationInCity($card->Location);
    }

    function locationInCity(string $location): bool
    {
        return 
         $location == Game::LOCATION_CITY_OLES_INN ||
         $location == Game::LOCATION_CITY_DOCKS ||
         $location == Game::LOCATION_CITY_FORUM ||
         $location == Game::LOCATION_CITY_BAZAAR ||
         $location == Game::LOCATION_CITY_GOVERNORS_GARDEN;
    }

    public function playerCanMove($playerId): bool
    {
        $characters = $this->getCharactersInPlayByPlayerId($playerId);
        $enGardeCharacters = array_filter($characters, function($character) { return $character->Engaged == false; });
        return count($enGardeCharacters) > 0;
    }

    public function playerCanRecruit($playerId): bool
    {
        //Get all characters that are in the city that have mercenaries at their location
        $charactersThatCanReruit = [];
        $charactersInCity = $this->getCharactersInCityByPlayerId($playerId);

        foreach ($charactersInCity as $character) {
            $charactersAtLocation = $this->getCharactersAtLocation($character->Location, $includeUncontrolled = true);
            $mercenariesAtLocation = array_filter($charactersAtLocation, fn($character) => ! $character->ControllerId && $character->hasTrait("Mercenary"));
            if (count($mercenariesAtLocation) > 0) {
                $charactersThatCanReruit[] = $character;
            }
        }

        return count($charactersThatCanReruit) > 0;        
    }

    public function playerCanEquip($playerId): bool
    {
        $charactersInCity = $this->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanEquipInCity = [];

        foreach ($charactersInCity as $character) {
            $attachmentsAtLocation = $this->getAvailableAttachmentsAtLocation($character->Location);
            if (count($attachmentsAtLocation) > 0) {
                $charactersThatCanEquipInCity[] = $character;
            }
        }

        return count($charactersThatCanEquipInCity) > 0 || $this->game->handHasAttachments($playerId);        
    }

    public function playerCanEquipToOpponents($playerId): bool
    {
        $handAttachments = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        $handAttachments = array_filter($handAttachments, fn($attachment) => $attachment instanceof FactionAttachment && $attachment->CanEquipToOpponents);

        return count($handAttachments) > 0;
    }

    public function playerCanBasicChallenge($playerId): bool
    {
        $characters = $this->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanChallenge = [];
        foreach ($characters as $character) 
        {
            if ($character instanceof _01178)
            {
                if (! $character->canChallenge()) continue;
            }
            else
            {
                if ( ! $character->canChallenge() || $character->Engaged) continue;
            }

            $otherCharacters = $this->getCharactersAtLocation($character->Location);
            $otherCharacters = array_filter($otherCharacters, fn($otherCharacter) => $otherCharacter->isNotControlledByPlayer($playerId));

            if (count($otherCharacters) > 0)
                $charactersThatCanChallenge[] = $character;
        }
        
        return count($charactersThatCanChallenge) > 0;
    }

    public function playerCanBasicClaim($playerId): bool
    {
        $characters = $this->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanClaim = [];
        foreach ($characters as $character) {
            if ($character->Engaged)
                continue;
            if ($character->DashedInfluence)
                continue;
            
            $charactersThatCanClaim[] = $character;
        }
        
        return count($charactersThatCanClaim) > 0;
    }

    public function playerHasInPlayActions($playerId): bool
    {
        $actionAvailable = [];

        foreach ($this->Actions as $action)
        {
            if ($action->isAvailableToPlayer($playerId, $this))
            {
                $actionAvailable[] = $action;
            }
        }

        foreach ($this->cards as $card)
        {
            if ($card instanceof IHasActions && $card->Location != Game::LOCATION_HAND)            
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    if ($action->isAvailableToPlayer($playerId, $this))
                    {
                        $actionAvailable[] = $action;
                    }
                }
            }
        }

        return count($actionAvailable) > 0;
    }

    public function playerHasInHandActions($playerId): bool
    {
        $actionCards = [];
        $cards = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        foreach ($cards as $card)
        {
            if ($card instanceof IHasActions)
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    if ($action->isAvailableToPlayer($playerId, $this))
                    {
                        $actionCards[] = $card;
                    }
                }
            }
        }

        return count($actionCards) > 0;
    }

    public function playerHasBrutes($playerId): bool
    {
        $bruteCards = [];
        $cards = $this->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        foreach ($cards as $card)
        {
            if ($card->hasTrait("Brute"))
            {
                $bruteCards[] = $card;
            }
        }

        return count($bruteCards) > 0;
    }

    public function deleteManeuverEvents(string $maneuverId)
    {
        $this->db->deleteManeuverEvents($maneuverId);
    }

    public function deleteTechniqueEvents(string $techniqueId)
    {
        $this->db->deleteTechniqueEvents($techniqueId);
    }

    public function deletePressureResultEvents()
    {
        $this->db->deletePressureResultEvents();
    }

    public function deleteTransitionEvents(string $reactionId)
    {
        $this->db->deleteTransitionEvents($reactionId);
    }

    public function deleteTransitionEventsBySourceId(int $sourceId)
    {
        $this->db->deleteTransitionEventsBySourceId($sourceId);
    }

    public function deleteEventsTargetingCard(int $cardId)
    {
        $this->db->deleteEventsTargetingCard($cardId);
    }

    public function areTransitionEventsOfTypeForPlayerQueued(int $playerId, string $reactionType): bool
    {
        return $this->db->areTransitionEventsOfTypeForPlayerQueued($playerId, $reactionType);
    }

    public function deleteActionTriggeredEvents(string $actionId)
    {
        $this->db->deleteActionTriggeredEvents($actionId);
    }

    public function deleteRiskReactionTriggeredEvents(string $reactionId)
    {
        $this->db->deleteRiskReactionTriggeredEvents($reactionId);
    }

    public function swapParticipantsInDuel(int $duelId, int $round, int $oldParticipantId, int $newParticipantId)
    {
        $sql = "SELECT challenging_player_id, challenger_id, defending_player_id, defender_id FROM duel where duel_id = $duelId";
        $duelValues = $this->db->getObjectList($sql)[0];
        $challengerId = $duelValues['challenger_id'];
        $defenderId = $duelValues['defender_id'];

        $oldParticipant = $this->getCharacterById($oldParticipantId);
        $newParticipant = $this->getCharacterById($newParticipantId);

        if ($oldParticipantId == $challengerId)
        {
            $sql = "UPDATE duel SET challenger_id = $newParticipantId WHERE duel_id = $duelId";
            $this->db->executeSql($sql);

            $sql = "UPDATE duel_round SET challenger_id = $newParticipantId WHERE duel_id = $duelId AND round = $round";
            $this->db->executeSql($sql);

            //Reset the conditions for challenger
            $oldParticipant->removeCondition(Game::DUEL_CHALLENGER);
            $oldParticipant->IsUpdated = true;

            $newParticipant->addCondition(Game::DUEL_CHALLENGER);
            $newParticipant->IsUpdated = true;

            $this->game->globals->set(Game::CHOSEN_PERFORMER, $newParticipant->Id);

            $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent($oldParticipant->ControllerId, $oldParticipant->Id, $newParticipant->Id);
            $this->queueEvent($challengerSwappedEvent);
        }
        else
        {
            $sql = "UPDATE duel SET defender_id = $newParticipantId WHERE duel_id = $duelId";
            $this->db->executeSql($sql);

            $sql = "UPDATE duel_round SET defender_id = $newParticipantId WHERE duel_id = $duelId AND round = $round";
            $this->db->executeSql($sql);

            //Reset the conditions for defender
            $oldParticipant->removeCondition(Game::DUEL_DEFENDER);
            $oldParticipant->IsUpdated = true;

            $newParticipant->addCondition(Game::DUEL_DEFENDER);
            $newParticipant->IsUpdated = true;

            $defenderSwappedEvent = EventFactory::createDefenderSwappedEvent($oldParticipant->ControllerId, $oldParticipant->Id, $newParticipant->Id);
            $this->queueEvent($defenderSwappedEvent);
        }

        //Update the actor in the round
        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $actorId = $this->db->getUniqueValue($sql);
        if ($actorId == $oldParticipantId)
        {
            $serialized = addslashes(serialize($newParticipant));
            $sql = "UPDATE duel_round SET actor_id = $newParticipantId, actor_serialized = '$serialized' WHERE duel_id = $duelId AND round = $round";
            $this->db->executeSql($sql);

            $this->game->notify->all("duelActorSwapped", '', [
                'round' => $round,
                'actor' => $newParticipant->getPropertyArray($this->game),
            ]);
        }
    }

    public function getDuelChallengerId() : ?int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $sql = "SELECT challenger_id FROM duel WHERE duel_id = $duelId";
        $result = $this->db->getUniqueValue($sql);
        return $result;
    }

    public function getDuelDefenderId() : int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $sql = "SELECT defender_id FROM duel WHERE duel_id = $duelId";
        $result = $this->db->getUniqueValue($sql);
        return $result;
    }

    public function getDuelRoundActor(): ?Character
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $round = $this->game->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT actor_id FROM duel_round where duel_id = $duelId AND round = $round";
        $actorId = $this->db->getUniqueValue($sql);
        return $this->getCharacterById($actorId);
    }

    function getDuelOpponentId($actorId) : int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $sql = "SELECT challenger_id, defender_id FROM duel WHERE duel_id = $duelId";
        $duel = $this->db->getObjectList($sql)[0];
        if ($duel['challenger_id'] == $actorId) {
            return $duel['defender_id'];
        }
        return $duel['challenger_id'];
    }

    public function getDuelRoundOpponent() : ?Character
    {
        $actor = $this->getDuelRoundActor();
        $opponentId = $this->getDuelOpponentId($actor->Id);
        $opponent = $this->getCharacterById($opponentId);

        //Get last known information about the opponent
        if ($this->game->characterIsInDiscardOrLocker($opponent))
        {
            $duelId = $this->game->globals->get(Game::DUEL_ID);
            $round = $this->game->globals->get(Game::DUEL_ROUND) - 1;

            if ($round > 0)
            {
                $sql = "SELECT actor_serialized FROM duel_round WHERE duel_id = $duelId AND round = $round";
                $result = $this->db->getObject($sql);
                $opponent = $this->game->safeUnserialize($result['actor_serialized']);
            }
        }

        return $opponent;
    }



    public function getCurrentDuelThreat($characterId) : int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $round = $this->game->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT challenger_id, defender_id, ending_challenger_threat, ending_defender_threat FROM duel_round where duel_id = $duelId AND round = $round";
        $result = $this->db->getObjectList($sql)[0];
        if ($characterId == $result['challenger_id'])
        {
            return $result['ending_challenger_threat'];
        }
        else if ($characterId == $result['defender_id'])
        {
            return $result['ending_defender_threat'];
        }

        return 0;
    }

    public function getCurrentRoundThrust(): int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $round = $this->game->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT COALESCE(technique_thrust, 0) + COALESCE(maneuver_thrust, 0) + COALESCE(combat_thrust, 0) as total_thrust 
                FROM duel_round 
                WHERE duel_id = $duelId AND round = $round";
        return (int) $this->db->getUniqueValue($sql);
    }

    public function getCurrentRoundRiposte(): int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $round = $this->game->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT COALESCE(technique_riposte, 0) + COALESCE(maneuver_riposte, 0) + COALESCE(combat_riposte, 0) as total_riposte 
                FROM duel_round 
                WHERE duel_id = $duelId AND round = $round";
        return (int) $this->db->getUniqueValue($sql);
    }

    public function duelParticipantWoundsTaken(int $participantId) : int
    {
        $duelId = $this->game->globals->get(Game::DUEL_ID);
        $round = $this->game->globals->get(Game::DUEL_ROUND);
        $sql = "SELECT sum(wounds_taken) as wounds FROM duel_round WHERE duel_id = $duelId AND round <> $round AND actor_id = $participantId";
        $result = $this->db->getUniqueValue($sql);
        
        return $result;
    }

    public function attachmentsAvailableFromOpponentDiscardPile(int $opponentId, Character $performer): array
    {
        $handWealth = $this->game->handWealthCount($performer->ControllerId);

        $discardPileName = $this->game->getPlayerDiscardDeckName($opponentId);
        $cards = $this->getCardObjectsAtLocation($discardPileName);
        $cards = array_filter($cards, fn($card) => $card instanceof Attachment);
        $availableAttachments = [];
        foreach ($cards as $card)
        {
            [$discount, $explanations] = $this->getEquipDiscount($performer, $card);
            $cost = $card->WealthCost - $discount;
            if ($handWealth >= $cost)
            {
                [$hasRestrictions, $restrictionExplanation] = $this->game->hasEquipRestrictions($performer, $card);                
                if ($hasRestrictions || ! $card->canAttachTo($performer))
                {
                    continue;
                }

                $availableAttachments[] = $card;
            }
        }
        return $availableAttachments;
    }

    public function risksAvailableFromDiscardPile(Character $performer): array
    {
        $handWealth = $this->game->handWealthCount($performer->ControllerId);

        $discardPileName = $this->game->getPlayerDiscardDeckName($performer->ControllerId);
        $cards = $this->getCardObjectsAtLocation($discardPileName);
        $cards = array_filter($cards, fn($card) => $card instanceof Risk);

        $availableRisks = [];
        foreach ($cards as $card)
        {
            if ($card instanceof IHasActions)
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    if ($action->isAvailableToPlayer($performer->ControllerId, $this, $overrideInHandCheck = true))
                    {
                        [$discount, $explanations] = $this->getActionFromHandDiscount($performer, $action);
                        $cost = $card->WealthCost - $discount;
                        if ($handWealth >= $cost)
                        {
                            $availableRisks[$card->Id] = $card;
                        }
                    }
                }
            }
        }

        return array_values($availableRisks);
    }

    public function calculateInHandPayDiscount(int $playerId, int $payStateType, int $cardId, string $internalId)
    {
        $discount = 0;
        $explanations = '';

        if ($payStateType == Game::PAY_STATE_IN_HAND_ACTION)
        {
            $actionId = $this->game->globals->get(GAME::CHOSEN_ACTION);
            $action = $this->getInHandActionById($actionId);
            $performer = null;
            if ($action->RequiresPerformerSelected)
            {
                $performerId = $this->game->globals->get(Game::CHOSEN_PERFORMER);
                $performer = $this->getCharacterById($performerId);
            }

            [$discount, $explanations] = $this->getActionFromHandDiscount($performer, $action);           
        }

        if ($payStateType == Game::PAY_STATE_IN_HAND_REACTION)
        {
            $card = $this->getCardById($cardId);
            if ($card instanceof IHasReactions)
                $reaction = $card->getReactionById($internalId);
            else
                throw new \BgaUserException(sprintf($this->game->translate("Card %d - %s does not have reactions"), $cardId, $card->Name));
            
            [$discount, $explanations] = $this->getReactionFromHandDiscount($reaction);
        }

        if ($payStateType == Game::PAY_STATE_EQUIP_ATTACHMENT)
        {
            $performerId = $this->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $this->getCharacterById($performerId);
            $attachment = $this->getAttachmentById($cardId);

            [$discount, $explanations] = $this->getEquipDiscount($performer, $attachment);
        }

        if ($payStateType == Game::PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD)
        {
            $combatCard = $this->getCardById($cardId);
            [$discount, $explanations] = $this->getManeuverFromCombatCardDiscount($combatCard);
        }

        if ($discount != 0)
        $this->game->notify->player($playerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
            "explanations" => $explanations,
        ]);

        $this->game->globals->set(Game::DISCOUNT, $discount);
        $this->game->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);

        return [$discount, $explanations];
    }
}
