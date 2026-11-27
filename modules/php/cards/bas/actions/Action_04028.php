<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04028 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Target Opposing Character; Move Both to Controlled or Leader City Location");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * City locations you claim-control, or where your Leader is — excluding the performer's current location.
     *
     * @return list<string>
     */
    private function getValidDestinations(Theah $theah, Character $performer): array
    {
        $playerId = $performer->ControllerId;
        $leader = $theah->getLeaderByPlayerId($playerId);
        $destinations = [];

        foreach ($theah->getCityLocations() as $cityLocation)
        {
            $name = $cityLocation->Name;
            // WHY exclude current: "Move … to" is a no-op on the same spot.
            if ($name === $performer->Location)
            {
                continue;
            }

            $controller = $theah->game->getControllerForLocation($name);
            $youControl = $controller == $playerId;
            $leaderThere = $leader !== null && $leader->Location === $name;

            if ($youControl || $leaderThere)
            {
                $destinations[] = $name;
            }
        }

        return $destinations;
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId));
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah, $owner)
            {
                // WHY En Garde Musketeer Action: En Garde = precondition (not Engage cost);
                // Musketeer = mechanical trait gate (not Sorcerer).
                if ($performer->Engaged || ! $performer->hasTrait("Musketeer", $owner))
                {
                    return false;
                }

                if (count($this->getValidTargets($theah, $performer)) == 0)
                {
                    return false;
                }

                return count($this->getValidDestinations($theah, $performer)) > 0;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04028", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        // WHY: Both moves only when EventCharacterTargeted survives (Cloud Bottle / UL / Maryam
        // cancel it). Nothing is a cost; moves share the targeting batchId so a late
        // deleteEventBatch strips them together. ActionResolved stays unbatched (like 01160).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $game = $theah->game;
            $owner = $this->getOwningCard($theah);
            $target = $theah->getCharacterById($event->targetId);
            $performer = $theah->getCharacterById((int)$game->globals->get(Game::CHOSEN_PERFORMER));
            $location = (string)$game->globals->get(Game::CHOSEN_LOCATION);
            if ($owner === null || $target === null || $performer === null || $location === '')
            {
                return;
            }

            $batchId = $event->batchId ?? $game->getNextEventBatchId();

            // WHY engage=false: En Garde is only a precondition; printed text has no Engage cost.
            $moveTarget = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $target->Id,
                $target->Location,
                $location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $moveTarget->batchId = $batchId;
            $theah->eventCheck($moveTarget);
            $theah->queueEvent($moveTarget);

            $movePerformer = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $location,
                $engage = false,
                $owner->Id,
                $this->Id
            );
            $movePerformer->batchId = $batchId;
            $theah->eventCheck($movePerformer);
            $theah->queueEvent($movePerformer);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04028)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04028_2)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);

            $args["performerId"] = $performerId;
            $args["characterId"] = $targetId;
            $args["locationIds"] = $performer !== null
                ? $this->getValidDestinations($game->theah, $performer)
                : [];
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, $game->translate("Performer not found")];
        }

        if (! $character->isControlled() || $character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You must target an opposing character.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04028)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} uses ${card_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04028_2)
        {
            $location = $ids[0];

            if ($game->theah->getCityLocation($location) == null)
            {
                throw new UserException($game->translate("Location not found"));
            }

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            $validDestinations = $this->getValidDestinations($game->theah, $performer);
            if (! in_array($location, $validDestinations))
            {
                throw new UserException($game->translate("Location is not a City location you control, or one where you control a Leader."));
            }

            $targetId = (int)$game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: Targeting fires at the FINAL confirm (destination), not at the character pick:
            // the character pick routes straight into the `_2` destination GameState (with Back),
            // so firing earlier would leave a stale targeting event if the player backs out.
            // Same seat as Teens / 04040 (targeting after all choices). The destination rides
            // CHOSEN_LOCATION so handleEvent can emit the moves only on survival.
            $game->globals->set(Game::CHOSEN_LOCATION, $location);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} targets ${target_inject_code}, moving them and ${performer_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "target_inject_code" => $target->getInjectCode(),
                "performer_inject_code" => $performer->getInjectCode(),
                "location_name" => $location,
            ]);

            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $performer->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
