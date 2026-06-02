<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01064 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move an Adjacent Renown to Guillén's Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $guillen = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($guillen))
        {
            return false;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) == 0)
        {
            return false;
        }

        $adjacentLocations = $theah->getAdjacentCityLocations($guillen->Location);
        foreach ($adjacentLocations as $location)
        {
            $reknown = $theah->game->getRenownForLocation($location);
            if ($reknown > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01064", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01064_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $args["performerId"] = $owner->Id;

            $availableLocations = [];
            $adjacentLocations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            foreach ($adjacentLocations as $locationName)
            {
                $location = $game->theah->getCityLocation($locationName);
                if ($location->Renown > 0)
                {
                    $availableLocations[] = $locationName;
                }
            }
            $args["locationIds"] = $availableLocations;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01064)
        {
            $guillen = $this->getOwningCharacter($game->theah);
            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new UserException($game->translate("Card is not in your hand"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $card->Id);

            $game->gamestate->nextState("cardChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01064_2)
        {
            $location = $ids[0];

            $owner = $this->getOwningCard($game->theah);
            $locations = $game->theah->getAdjacentCityLocations($owner->Location);
            if (! in_array($location, $locations))
            {
                throw new UserException(sprintf($game->translate('Location %s is not adjacent to %s.'), $location, $owner->Location));
            }

            $cardId = $game->globals->get(Game::CHOSEN_CARD);
            $event = EventFactory::createCardDiscardedFromHandEvent($owner->ControllerId, $cardId, $owner->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createRenownRemovedFromLocationEvent($owner->ControllerId, $location, 1, $owner->getInjectCode());
            $game->theah->queueEvent($event);

            $event = EventFactory::createReknownAddedToLocationEvent($owner->ControllerId, $owner->Location, 1, $owner->getInjectCode(), $isMove = true);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
