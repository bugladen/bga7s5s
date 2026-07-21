<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03009 extends RiskAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Performer to Adjacent Location with Enemy or Available Mercenary");
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
        $performers = array_filter($performers, fn(Character $character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega"));
        $performers = array_values(array_filter($performers, fn(Character $performer) => count($this->getValidDestinations($theah, $performer)) > 0));

        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03009", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03009)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03009)
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

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id);
            $game->theah->queueEvent($sorceryStartEvent);

            $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $performer->Id, $performer->Location, $location, $engage = false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id);
            $game->theah->queueEvent($sorceryPlayedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    private function getValidDestinations(Theah $theah, Character $performer): array
    {
        $adjacentLocations = $theah->getAdjacentCityLocations($performer->Location, $includeHome = false);

        return array_values(array_filter($adjacentLocations, function (string $location) use ($theah, $performer) {
            $characters = $theah->getCharactersAtLocation($location, $includeUncontrolled = true);
            foreach ($characters as $character)
            {
                if ($character->isControlled() && $character->ControllerId != $performer->ControllerId)
                {
                    return true;
                }
                if (! $character->isControlled() && $character->hasTrait("Mercenary"))
                {
                    return true;
                }
            }
            return false;
        }));
    }
}
