<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04030 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Performer to Adjacent City Location with More Renown");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => count($this->getValidDestinations($theah, $performer)) > 0
        ));
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        // WHY: City Action pay uses this hook; combat-card pay uses getManeuverFromCombatCardDiscount on Maneuver_04030.
        if ($action->Id == $this->Id)
        {
            if ($performer === null)
            {
                return $discount;
            }

            if ($performer->hasTrait("Merchant") || $performer->hasTrait("Scoundrel"))
            {
                $discount += 1;
                $owner = $this->getOwningCard($theah);
                $explanations[] = sprintf(
                    $theah->game->translate("%s: -1 because your performer is a Merchant or Scoundrel."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04030", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04030)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["performerId"] = $performer->Id;
            $args["locationIds"] = $this->getValidDestinations($game->theah, $performer);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04030)
        {
            $location = $ids[0];
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $validDestinations = $this->getValidDestinations($game->theah, $performer);
            if (! in_array($location, $validDestinations))
            {
                throw new UserException(sprintf($game->translate("%s cannot move to this location."), $performer->Name));
            }

            $owner = $this->getOwningCard($game->theah);

            $moveEvent = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    /**
     * @return list<string>
     */
    private function getValidDestinations(Theah $theah, Character $performer): array
    {
        $performerLocation = $theah->getCityLocation($performer->Location);
        if ($performerLocation === null)
        {
            return [];
        }

        $adjacentLocations = $theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
        $validDestinations = [];

        foreach ($adjacentLocations as $adjacentLocationName)
        {
            $adjacentLocation = $theah->getCityLocation($adjacentLocationName);
            if ($adjacentLocation !== null && $adjacentLocation->Renown > $performerLocation->Renown)
            {
                $validDestinations[] = $adjacentLocationName;
            }
        }

        return $validDestinations;
    }
}
