<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;

class _01151 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Shifting Tides");

        $this->Image = "img/cards/7s5s/151.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 151;

        $this->Initiative = 1;
        $this->PanacheModifier = 1;

        $this->Traits = [
            "Nature"
        ];

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSchemeCardRevealed && $event->schemeId == $this->Id)
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
            Next, ${player_name} may choose a city location to place Renown onto. 
            Last, ${player_name} will choose a player to add another Renown to a location of their choice.'), [
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

                if ($location->Reknown > 0)
                {
                    $renownEvent = EventFactory::createReknownRemovedFromLocationEvent($this->ControllerId, $location->Name, $location->Reknown, $this->getInjectCode());
                    $event->theah->eventCheck($renownEvent);
                    $event->theah->queueEvent($renownEvent);
                }
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "01151");
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_3)
        {
            $args["locationIds"] = array_map(fn($location) => $location->Name, array_values($game->theah->getCityLocations()));
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2)
        {
            $playerInfo = $game->loadPlayersBasicInfos();
            $opponents = array_filter($playerInfo, fn($player) => (int)$player["player_id"] != $this->ControllerId);
            $opponents = array_map(fn($player) => [
                "id" => $player["player_id"],
                "name" => $player["player_name"],
            ], $opponents);
            $args["opponents"] = array_values($opponents);
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_3)
        {
            $location = $ids[0];
            $locations = $game->theah->getCityLocations();
            $locations = array_map(fn($location) => $location->Name, array_values($locations));
            if (!in_array($location, $locations))
            {
                throw new \BgaUserException($game->translate("Location is not a city location."));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to add a Renown to ${location}.'), [
                "i18n" => ["location"],
                "player_name" => $game->getActivePlayerName(),
                "location" => $location,
            ]);

            $renownEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($renownEvent);
            $game->theah->queueEvent($renownEvent);
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151)
        {
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01151_2");
            $game->theah->queueEvent($transition);
        }
        
        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151 || $state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_3)
        {
            $game->gamestate->nextState();
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01151_2)
        {
            $opponentId = (int)$id;
            $playerInfo = $game->loadPlayersBasicInfos();
            if (!isset($playerInfo[$opponentId]))
            {
                throw new \BgaUserException($game->translate("Invalid opponent."));
            }

            if ($this->ControllerId == $opponentId)
            {
                throw new \BgaUserException($game->translate("You cannot place a Renown onto a city location for yourself."));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen ${opponent_name} to place a Renown onto a city location.'), [
                "player_name" => $game->getActivePlayerName(),
                "opponent_name" => $playerInfo[$opponentId]["player_name"],
            ]);

            $transition = EventFactory::createTransitionEvent($opponentId, $this->Id, "01151_3");
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }
    }
}