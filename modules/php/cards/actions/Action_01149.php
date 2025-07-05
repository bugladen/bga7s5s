<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01149 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Move Performer to any City location From City Docks";
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        //Must have a performer at the docks
        $performers = $theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS);
        $performers = array_filter($performers, fn($performer) => $performer->ControllerId == $playerId);
        if (count($performers) == 0)
            return false;
        
        $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS);
        $numberofCityCards = 0;
        $numberofEventCards = 0;
        foreach ($cards as $card)
        {
            if ($card->isControlled())
                continue;

            if ($card instanceof ICityDeckCard)
            {
                $numberofCityCards++;
            }
            if ($card instanceof CityEventCard)
            {
                $numberofEventCards++;
            }
        }

        //Can't use if there are city cards in the docks
        if ($numberofCityCards > 0)
            return false;

        //Can't use if there are more city cards than event cards
        if ($numberofCityCards > $numberofEventCards)
            return false;

        //No city cards or only event cards in the docks
        return true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->ControllerId == $playerId));
        
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $scheme = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $scheme->Id, "01149");
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        $scheme = $this->getOwningCard($game->theah);
        $args['schemeId'] = $scheme->Id;

        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $args['performerId'] = $performerId;

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01149)
        {
            $location = $game->theah->getCityLocation($ids[0]);

            $locations = $game->theah->getCityLocations();
            $locations = array_filter($locations, fn($validLocation) => $validLocation->Name == $location->Name);
            if (count($locations) == 0)
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not a valid location."), $location->Name));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCardById($performerId);

            $moveEvent = EventFactory::createCardMovedEvent($performer->ControllerId, $performer->Id, $performer->Location, $location->Name, $engage = false);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the ${action} Action'), [
                'i18n' => ['action'],
                'player_name' => $game->getActivePlayerName(),
                'action' => 'Midnight Shipment',
            ]);

            $this->SetUsed($game->theah, true);

            $game->gamestate->nextState("locationChosen");
        }
    }
}