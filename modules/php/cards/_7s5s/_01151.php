<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardWhenRevealedEffect;

class _01151 extends Scheme
{
    public Array $locations = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Shifting Tides");

        $this->Image = "01151v2.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 151;

        $this->Initiative = 1;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate("Nature")
        ];

        $this->Text = clienttranslate("<p>When this scheme is revealed, discard all City Cards from each City location. (\"When reveal\" is before any other scheme.)</p><p>Add a City Card to each City location. Then, discard all Renown from all locations and add a Renown to a location. Then, each opponent adds a Renown to a different location. (During normal initiative order.)</p>");

        $this->resetCard();
    }

    public function hasWhenRevealedEffect(): bool
    {
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCardWhenRevealedEffect && $event->cardId == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now activates its When Revealed effect. 
            All City Cards in play at City Locations will be discarded to the City Discard Pile.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $cityCards = $event->theah->getAllCards();
            $cityCards = array_filter($cityCards, fn($card) => ! $card->isControlled() && $card instanceof ICityDeckCard);
            foreach ($cityCards as $card)
            {
                $discard = EventFactory::createCardAddedToCityDiscardPileEvent($this->ControllerId, $card->Id, $card->Location, $this->Id, $asEffect = true);
                $event->theah->queueEvent($discard);
            }
        }

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. 
            A City Card will be added to each City Location. Then Renown will be discarded from each City Location.
            Next, ${player_name} must choose a city location to place Renown onto. 
            Last, all opponents must choose to add another Renown to a different location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $locations = $event->theah->getCityLocations();
            $game = $event->theah->game;
            $deck = $game->getGameDeckObject();
            foreach ($locations as $location)
            {
                $cityCard = $game->getCardsOnTopOfCityDeck(1)[0];

                $deck->moveCard($cityCard['id'], $location->Name);

                $cardEvent = EventFactory::createCityCardAddedToLocationEvent($cityCard['id'], $location->Name);
                $event->theah->queueEvent($cardEvent);

                if ($location->Renown > 0)
                {
                    $renownEvent = EventFactory::createReknownRemovedFromLocationEvent($this->ControllerId, $location->Name, $location->Renown, $this->getInjectCode());
                    $event->theah->eventCheck($renownEvent);
                    $event->theah->queueEvent($renownEvent);
                }
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01151");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhasePlanningEnd)
        {
            $this->locations = [];
            $this->IsUpdated = true;
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2)
        {
            $locations = array_map(fn($location) => $location->Name, array_values($game->theah->getCityLocations()));

            if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2)
            {
                $locations = array_values(array_filter($locations, fn($name) => !in_array($name, $this->locations)));
            }

            $args["locationIds"] = $locations;
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2)
        {
            $location = $ids[0];
            $locations = $game->theah->getCityLocations();
            $locations = array_map(fn($location) => $location->Name, array_values($locations));
            if (!in_array($location, $locations))
            {
                throw new UserException($game->translate("Location is not a city location."));
            }

            if (in_array($location, $this->locations))
            {
                throw new UserException($game->translate("Location has already been chosen."));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to add a Renown to ${location}.'), [
                "i18n" => ["location"],
                "player_name" => $game->getPlayerNameById($this->ControllerId),
                "location" => $location,
            ]);

            $this->locations[] = $location;
            $this->IsUpdated = true;

            $renownEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $renownEvent->priority = Event::HIGHEST_PRIORITY;
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151)
        {
            $sql = "SELECT player_id, selected_scheme_id as schemeId FROM player ORDER by turn_order";
            $list = $game->getCollectionFromDB($sql);
            foreach ( $list as $playerId => $player ) 
            {
                if ($player['player_id'] == $this->ControllerId)
                {
                    continue;
                }

                $transition = EventFactory::createTransitionEvent($playerId, $this->Id, "01151_2");
                $transition->priority = Event::HIGH_PRIORITY;
                $game->theah->queueEvent($transition);
            }    
        }
        
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2)
        {
            $game->gamestate->nextState();
        }
    }
}