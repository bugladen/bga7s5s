<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03056 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Opposing Character Claims; Move Renown");
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
            fn(Character $performer) => count($this->getValidTargets($theah, $performer)) > 0
        ));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        $controller = $game->getControllerForLocation($character->Location);
        if ($controller == $character->ControllerId)
        {
            return [false, $game->translate("Their controller already controls this location.")];
        }

        // WHY: Claim is half the printed effect — Leshiye / Indomitable Will etc. block claim.
        if (! $game->theah->canLocationBeClaimedBy($character->ControllerId, $character->Location))
        {
            return [false, $game->translate("This location cannot be claimed.")];
        }

        // WHY: "and you move a Renown" — both halves fire together; no Renown means dead payoff.
        $cityLocation = $game->theah->getCityLocation($character->Location);
        if ($cityLocation === null || $cityLocation->Renown < 1)
        {
            return [false, $game->translate("There is no Renown at this location to move.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03056", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03056)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performer->Id;
            $args['ids'] = array_map(fn(Character $c) => $c->Id, $this->getValidTargets($game->theah, $performer));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03056_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $sourceLocation = $game->globals->get(Game::CHOSEN_LOCATION);

            $args['performerId'] = $performerId;
            $args['sourceLocation'] = $sourceLocation;
            $args['locationIds'] = $this->getRenownDestinations($game->theah, $sourceLocation);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03056)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);
            // WHY: Stash source before claim/reactions — board may shift before the Renown chooser.
            $game->globals->set(Game::CHOSEN_LOCATION, $character->Location);

            // WHY: "they claim it" = target's controller via the targeted character (not you).
            if ($game->theah->canLocationBeClaimedBy($character->ControllerId, $character->Location))
            {
                $claimEvent = EventFactory::createLocationClaimedEvent(
                    $character->ControllerId,
                    $character->Id,
                    $character->Location
                );
                $game->theah->queueEvent($claimEvent);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${location} cannot be claimed.'), [
                    'i18n' => ['location'],
                    'location' => $character->Location,
                ]);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);
                $game->gamestate->nextState("targetChosen");
                return;
            }

            $cityLocation = $game->theah->getCityLocation($character->Location);
            if ($cityLocation !== null && $cityLocation->Renown > 0)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03056_2", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $game->gamestate->nextState("targetChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03056_2)
        {
            $destination = $ids[0];
            $sourceLocation = $game->globals->get(Game::CHOSEN_LOCATION);
            $owner = $this->getOwningCard($game->theah);

            $validDestinations = $this->getRenownDestinations($game->theah, $sourceLocation);
            if (! in_array($destination, $validDestinations, true))
            {
                throw new UserException($game->translate("Invalid destination for Renown."));
            }

            $source = $game->theah->getCityLocation($sourceLocation);
            if ($source === null || $source->Renown < 1)
            {
                throw new UserException($game->translate("There is no Renown at this location to move."));
            }

            $batchId = $game->getNextEventBatchId();

            $movingEvent = EventFactory::createRenownMovingBetweenLocationsEvent(
                $owner->ControllerId,
                $sourceLocation,
                $destination,
                1,
                $owner->getInjectCode()
            );
            $movingEvent->batchId = $batchId;
            $game->theah->eventCheck($movingEvent);
            $game->theah->queueEvent($movingEvent);

            $removedEvent = EventFactory::createRenownRemovedFromLocationEvent(
                $owner->ControllerId,
                $sourceLocation,
                1,
                $owner->getInjectCode()
            );
            $removedEvent->batchId = $batchId;
            $game->theah->eventCheck($removedEvent);
            $game->theah->queueEvent($removedEvent);

            $addedEvent = EventFactory::createRenownAddedToLocationEvent(
                $owner->ControllerId,
                $destination,
                1,
                $owner->getInjectCode(),
                $isMove = true
            );
            $addedEvent->batchId = $batchId;
            $game->theah->eventCheck($addedEvent);
            $game->theah->queueEvent($addedEvent);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} moved a Renown from ${from_location} to ${to_location}.'), [
                "i18n" => ["from_location", "to_location"],
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "from_location" => $sourceLocation,
                "to_location" => $destination,
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        $cityLocation = $theah->getCityLocation($performer->Location);
        if ($cityLocation === null || $cityLocation->Renown < 1)
        {
            return [];
        }

        return array_values(array_filter(
            $opposing,
            function (Character $character) use ($theah, $performer) {
                $controller = $theah->game->getControllerForLocation($performer->Location);
                if ($controller == $character->ControllerId)
                {
                    return false;
                }

                return $theah->canLocationBeClaimedBy($character->ControllerId, $performer->Location);
            }
        ));
    }

    /**
     * @return list<string>
     */
    private function getRenownDestinations(Theah $theah, string $sourceLocation): array
    {
        $destinations = [];
        foreach ($theah->getCityLocations() as $cityLocation)
        {
            if ($cityLocation->Name != $sourceLocation)
            {
                $destinations[] = $cityLocation->Name;
            }
        }

        return $destinations;
    }
}
