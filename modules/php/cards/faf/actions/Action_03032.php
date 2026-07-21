<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03032 extends RiskCityAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Performer, Move to Any Location, Perform Another Action");
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

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Sorcerer")
                && count($this->getValidDestinationLocations($theah, $performer)) > 0
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03032", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03032)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["performerId"] = $performer->Id;
            $args["locationIds"] = $this->getValidDestinationLocations($game->theah, $performer);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03032)
        {
            $location = $ids[0];
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($game->theah);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found."));
            }

            $validDestinations = $this->getValidDestinationLocations($game->theah, $performer);
            if (! in_array($location, $validDestinations, true))
            {
                throw new UserException(sprintf($game->translate("%s cannot move to this location."), $performer->Name));
            }

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id
            );
            $game->theah->queueEvent($sorceryStartEvent);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $performer->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);

            $moveEvent = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $location,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performer->Id,
                0,
                $location
            );
            $game->theah->queueEvent($sorceryPlayedEvent);

            // WHY: mandatory follow-up action locked to this performer — consumed via Game::EXTRA_ACTION_PERFORMER
            $game->globals->set(Game::EXTRA_ACTIONS, 1);
            $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $performer->Id);

            $game->notify->all("message", clienttranslate('${player_name} must perform another action with ${performer_name}.'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "performer_name" => $performer->Name,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    /**
     * @return list<string>
     */
    private function getValidDestinationLocations(Theah $theah, Character $performer): array
    {
        $locations = array_map(
            fn($location) => $location->Name,
            $theah->getCityLocations()
        );

        if ($performer->Location != Game::LOCATION_PLAYER_HOME)
        {
            $locations[] = Game::LOCATION_PLAYER_HOME;
        }

        return array_values(array_filter(
            $locations,
            fn(string $location) => $location != $performer->Location
        ));
    }
}
