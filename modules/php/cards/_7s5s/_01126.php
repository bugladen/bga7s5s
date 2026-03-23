<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;

class _01126 extends Scheme
{
    public string $ChosenLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Leshiye of the Wood");
        $this->Image = "01126.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 126;

        $this->initializeFaction("Ussura");
        $this->Initiative = 34;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Leshiye", 
            "Nature",
        ];

        $this->Text = "<p>Choose one of the outermost locations. Add a Renown to two other locations.</p><p>Place this card on the chosen location. Discard all City Cards and Renown there. All characters there go Home and Renown cannot be added or moved to or from the location. It cannot be controlled. At the end of the Day, send this card to The Locker.</p>";

        $this->resetCard();
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);
        $properties['chosenLocation'] = $this->ChosenLocation;
        return $properties;
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventReknownAddedToLocation) 
        {
            if ($event->location == $this->ChosenLocation)
                throw new \BgaUserException($event->theah->game->translate(("Leshiye of the Wood does not allow Renown to be placed at its location.")));    
        }

        //We have to allow the reknown to be removed by the scheme itself
        if ($event instanceof EventReknownRemovedFromLocation && $event->source != $this->Name) 
        {
            if ($event->location == $this->ChosenLocation)
                throw new \BgaUserException($event->theah->game->translate(("Leshiye of the Wood does not allow Renown to be removed from its location.")));    
        }

        if ($event instanceof EventLocationClaimed && $event->location == $this->ChosenLocation)
        {
            throw new \BgaUserException($event->theah->game->translate(("Leshiye of the Wood does not allow locations to be claimed at its location.")));    
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. 
            ${player_name} may first choose an outermost city location. Then they will choose two locations to place reknown onto. '), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose a location.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01126");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventSchemeMovedToCity && $event->scheme == $this)
        {
            $playerId = $event->theah->game->getActivePlayerId();
            $deck = $event->theah->game->getGameDeckObject();

            $event->theah->game->notify->all('01126_2_scheme_moved', 
                clienttranslate('${scheme_inject_code} moved to ${location}'), [
                    'i18n' => ['location'],
                    "scheme_inject_code" => $this->getInjectCode(),
                    "cardId" => $this->Id,
                    "location" => $event->location,
            ]);    

            //Get all cards in the chosen location
            $cards = $event->theah->getCardObjectsAtLocation($this->ChosenLocation);
            foreach ($cards as $card)
            {
                //Discard all city cards
                if ($card instanceof ICityDeckCard)
                {
                    $discard = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $card->Id, $this->ChosenLocation, $this->Id, $asEffect = true);
                    $event->theah->queueEvent($discard);
                }

                //All characters go home
                else if ($card instanceof Character)
                {
                    $deck->moveCard($card->Id, Game::LOCATION_PLAYER_HOME, $card->ControllerId);;

                    $movedHome = EventFactory::createCardMovingEvent($this->ControllerId, $card->Id, $this->ChosenLocation, Game::LOCATION_PLAYER_HOME, false);
                    $event->theah->queueEvent($movedHome);
                }
            }

            //Discard all reknown at chosen location
            $location = $event->theah->getCityLocation($this->ChosenLocation);
            if ($location->Reknown > 0)
            {
                $reknown = $event->theah->createEvent(Events::ReknownRemovedFromLocation);
                if ($reknown instanceof EventReknownRemovedFromLocation)
                {
                    $reknown->location = $this->ChosenLocation;
                    $reknown->amount = $location->Reknown;
                    $reknown->source = $this->Name;
                }
                $event->theah->queueEvent($reknown);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2)
        {
            $args["chosenLocation"] = $this->ChosenLocation;
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01126)
        {
            $location = $ids[0];

            if (!in_array($location, $game->theah->getOuterCityLocations()))
            {
                throw new \BgaUserException($game->translate("Location is not an outer city location."));
            }

            $this->ChosenLocation = $location;
            $game->updateCardObjectInDb($this);
            $game->theah->addCardToWorld($this);
            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $game->gamestate->nextState("locationChosen");
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01126_2)
        {
            $playerId = $game->getActivePlayerId();
            $playerName = $game->getActivePlayerName();
    
            $locations = $ids;
    
            //Check to be sure renown can be added to locations
            foreach ($locations as $location) {
                $reknownEvent = EventFactory::createReknownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
                $game->theah->eventCheck($reknownEvent);
            }
    
            //Check if event can be run
            $schemeMoveEvent = $game->theah->createEvent(Events::SchemeMovedToCity);
            if ($schemeMoveEvent instanceof EventSchemeMovedToCity) {
                $schemeMoveEvent->scheme = $this;
                $schemeMoveEvent->location = $this->ChosenLocation;
                $schemeMoveEvent->playerId = $playerId;
            }
            $game->theah->eventCheck($schemeMoveEvent);
    
            $game->notify->all('message', 
                clienttranslate('${player_name} has chosen ${location} as the Chosen Location for ${scheme_inject_code}.'), [
                'i18n' => ['location'],
                "player_name" => $playerName,
                "location" => $this->ChosenLocation,
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            foreach ($locations as $location) {
                $reknownEvent = EventFactory::createReknownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
                $game->theah->queueEvent($reknownEvent);
            }
    
            // Move Leshiye of the Wood to the chosen location
            $game->theah->queueEvent($schemeMoveEvent);
    
            // Go back and finish running the Scheme events
            $game->gamestate->nextState("locationsChosen");
        }
    }
}