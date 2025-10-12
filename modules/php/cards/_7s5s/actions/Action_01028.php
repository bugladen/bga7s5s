<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01028 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Move Thugs and Pressure City Location');
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $thugs = $theah->getCharactersInCityByPlayerId($playerId);
        $thugs = array_filter($thugs, fn($thug) => $thug->hasTrait('Thug'));

        return count($thugs) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01028", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01028_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $locations = $game->theah->getAdjacentCityLocations($location);
            $thugs = [];
            foreach ($locations as $location)
            {
                $characters = $game->theah->getCharactersAtLocation($location);
                $characters = array_values(array_filter($characters, fn($c) => $c->ControllerId == $owner->ControllerId && $c->hasTrait('Thug')));
                $thugs = array_merge($thugs, $characters);
            }
            $args["ids"] = array_map(fn($thug) => $thug->Id, $thugs);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01028)
        {
            $location = $ids[0];
            $owner = $this->getOwningCard($game->theah);

            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01028_2)
        {
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $owner = $this->getOwningCard($game->theah);
            $pressureBonus = 0;
            $adjacentLocations = $game->theah->getAdjacentCityLocations($location);
            
            foreach ($ids as $id)
            {
                $thug = $game->theah->getCharacterById($id);
                if ($thug == null)
                {
                    throw new \BgaUserException($game->translate("Character not found"));
                }

                if ($thug->ControllerId != $owner->ControllerId)
                {
                    throw new \BgaUserException($game->translate("You do not control that thug"));
                }

                if (! in_array($thug->Location, $adjacentLocations))
                {
                    throw new \BgaUserException($game->translate("Thug is not adjacent to the chosen location"));
                }
                
                $moveEvent = EventFactory::createCardMovedEvent($owner->ControllerId, $thug->Id, $thug->Location, $location, $engage = false, $owner->Id);
                $game->theah->queueEvent($moveEvent);
                $pressureBonus++;
            }

            $performer = null;
            if (count($ids) > 0)
            {
                $performer = $game->theah->getCharacterById($ids[0]);
            }
            else
            {
                $characters = $game->theah->getCharactersAtLocation($location);
                $characters = array_values(array_filter($characters, fn($c) => $c->ControllerId == $owner->ControllerId));
                $performer = $characters[0];
            }

            if ($performer == null)
            {
                throw new \BgaUserException($game->translate("No thugs were moved and no performer found at the pressure location"));
            }

            $game->notifyAllPlayers("message", clienttranslate('${card_inject_code} ${count} Thugs were moved to the pressure location which will add +${bonus} to ${player_name}\'s Influence value'), [
                'card_inject_code' => $owner->getInjectCode(),
                'count' => count($ids),
                'bonus' => $pressureBonus,
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
            ]);


            $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
            $game->globals->set(Game::CLAIMING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_BONUS, $pressureBonus);

            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $owner->Id, $location, [Game::STAT_INFLUENCE]);
            $game->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01028_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $this->resetPlayerPassCount($game);            
            $game->gamestate->nextState("thugsChosen");
        }
    }
}
