<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\DB;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\reactions\Reaction_CrewCapLimit;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
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

    use EventHub;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
        $this->cityLocations = [];
        $this->db = new DB();
        $this->cityBuilt = false;

        $this->Reactions = [
            new Reaction_CrewCapLimit(),
        ];
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

        $this->buildCityLocations();

        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PLAYER_HOME);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_OLES_INN);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_FORUM);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_BAZAAR);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_CITY_GOVERNORS_GARDEN);
        $this->cards += $this->db->getCardObjectsAtLocation(Game::LOCATION_PURGATORY);
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
            $this->game->notifyAllPlayers("message", clienttranslate($e->getMessage()), []);
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
                if($event->getPlayerId()) {
                    $this->game->gamestate->changeActivePlayer($event->getPlayerId());
                }
                $this->game->globals->set(Game::TRANSITION_SOURCE_ID, $event->sourceId);
                $this->game->globals->set(Game::TRANSITION_INTERNAL_ID, $event->internalId);

                $this->game->gamestate->nextState($event->transition);
                return;
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
            $state = $this->game->gamestate->state();
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

    function getActionFromHandDiscount(Character $performer, CardAction $action): int
    {
        $cards = $this->cards;
        $discount = 0;
        foreach ($cards as $card) {
            $discount += $card->getActionFromHandDiscount($this, $performer, $action);
        }
            
        return $discount;
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
        if (array_key_exists($cardId, $this->cards)) {
            return $this->cards[$cardId];
        }

        $card = $this->db->getCardObject($cardId);
        if ($card) {
            $this->cards[$cardId] = $card;
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

    function getCharacterCountByPlayerId($playerId): int
    {
        $count = 0;
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->ControllerId == $playerId) {
                $count++;
            }
        }
        return $count;
    }

    function getCharacterById($id): ?Character
    {
        if (array_key_exists($id, $this->cards)) {
            return $this->cards[$id];
        }

        $card = $this->db->getCardObject($id);
        if ($card) {
            $this->cards[$id] = $card;
            return $card;
        }

        return null;
    }

    function getAttachmentById($id): ?Attachment
    {
        foreach ($this->cards as $card) {
            if ($card->Id == $id && $card instanceof Attachment) {
                return $card;
            }
        }
        return null;
    }

    function getCharactersInPlay(): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->isControlled() && $card->Location != Game::LOCATION_HAND) {
                $characters[] = $card;
            }
        }
        return $characters;
    }   

    
    function getCharactersInPlayByPlayerId($playerId): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->ControllerId == $playerId && $card->Location != Game::LOCATION_HAND) {
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

    function getCharactersAtHome($playerId): array
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->Location == Game::LOCATION_PLAYER_HOME && $card->ControllerId == $playerId) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getCharactersAtLocation($location)
    {
        $characters = [];
        foreach ($this->cards as $card) {
            if ($card instanceof Character && $card->isControlled() && $card->Location == $location) {
                $characters[] = $card;
            }
        }
        return $characters;
    }

    function getEquipDiscount(Character $performer, Attachment $attachment): int
    {
        //Smuggled Item cost is free
        if ($this->game->globals->get(Game::EQUIP_TYPE) == Game::SMUGGLED_ITEM_EQUIP_TYPE) {
            return $attachment->WealthCost;
        }
        
        $discount = 0;
        foreach ($this->cards as $card) {
            $discount += $card->getEquipDiscount($this, $performer, $attachment);
        }

        return $discount;
    }

    function getParleyDiscount(Character $performer, bool $parleying): int
    {
        $discount = 0;
        foreach ($this->cards as $card) {
            $discount += $card->getParleyDiscount($performer, $parleying);
        }

        return $discount;
    }

    function getInPlayActionsAvailableToPlayer($playerId)
    {
        $actionsArray = [];
        foreach ($this->cards as $card)
        {
            if ($card instanceof IHasActions && $card->Location != Game::LOCATION_HAND)
            {
                $actions = $card->getActionsArray($this->game);
                foreach ($actions as $actionItem)
                {
                    $action = $card->getActionById($actionItem['id']);
                    if ($action->isAvailableToPlayer($playerId, $this, $this->game))
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
                    if ($action->isAvailableToPlayer($playerId, $this, $this->game))
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
                    if ($action->isAvailableToPlayer($playerId, $this, $this->game))
                    {
                        $actionsArray[] = $card->Id;
                    }
                }
            }
        }

        //Filter out any duplicates
        $actionsArray = array_unique($actionsArray, SORT_REGULAR);

        return $actionsArray;
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

    function getInPlayActionById($id): ?CardAction
    {
        foreach ($this->cards as $card) {
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

    function getPressureTypes(Character $performer, string $startingStatType): Array
    {        
        $pressureTypes = [$startingStatType];
        $cardsInPlay = array_filter($this->cards, fn($card) => $this->cardInCity($card) || $card->Location == Game::LOCATION_PLAYER_HOME);
        foreach ($cardsInPlay as $card) {
            $card->getPressureTypes($this, $performer, $pressureTypes);
        }
        $pressureTypes = array_unique($pressureTypes);
        return $pressureTypes;
    }

    function getReactionFromHandDiscount(CardReaction $reaction): int
    {
        $discount = 0;
        foreach ($this->cards as $card) {
            $discount += $card->getReactionFromHandDiscount($this, $reaction);
        }
        return $discount;
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
            $influence += $character->Influence;
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
            $charactersAtLocation = $this->getCharactersAtLocation($character->Location);
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

    public function playerCanChallenge($playerId): bool
    {
        $characters = $this->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanChallenge = [];
        foreach ($characters as $character) 
        {
            if ( ! $character->canChallenge()) continue;

            $otherCharacters = $this->getCharactersAtLocation($character->Location);
            $otherCharacters = array_filter($otherCharacters, fn($otherCharacter) => $otherCharacter->ControllerId && $otherCharacter->ControllerId != $playerId );

            if (count($otherCharacters) > 0)
                $charactersThatCanChallenge[] = $character;
        }
        
        return count($charactersThatCanChallenge) > 0;
    }

    public function playerCanClaim($playerId): bool
    {
        $characters = $this->getCharactersInCityByPlayerId($playerId);
        $charactersThatCanClaim = [];
        foreach ($characters as $character) {
            if ($character->Engaged)
                continue;
            $charactersThatCanClaim[] = $character;
        }
        
        return count($charactersThatCanClaim) > 0;
    }

    public function playerHasInPlayActions($playerId): bool
    {
        $actionCards = [];
        foreach ($this->cards as $card)
        {
            if ($card instanceof IHasActions && $card->Location != Game::LOCATION_HAND)            
            {
                $actions = $card->getActions();
                foreach ($actions as $action)
                {
                    if ($action->isAvailableToPlayer($playerId, $this, $this->game))
                    {
                        $actionCards[] = $card;
                    }
                }
            }
        }

        return count($actionCards) > 0;
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
                    if ($action->isAvailableToPlayer($playerId, $this, $this->game))
                    {
                        $actionCards[] = $card;
                    }
                }
            }
        }

        return count($actionCards) > 0;
    }

    public function upsertCard(Card $card)
    {
        $this->cards[$card->Id] = $card;
    }

    public function deleteManeuverEvents(string $maneuverId)
    {
        $this->db->deleteManeuverEvents($maneuverId);
    }

    public function deleteTechniqueEvents(string $techniqueId)
    {
        $this->db->deleteTechniqueEvents($techniqueId);
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

            $this->game->notifyAllPlayers("duelActorSwapped", '', [
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
}
