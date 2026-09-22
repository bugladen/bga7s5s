<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04cd29 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Engaged Character at Adjacent Location");
    }

    /**
     * Engaged characters at adjacent City locations with lower Finesse than Tijani.
     *
     * @return Character[]
     */
    public function getEligibleTargets(Theah $theah, Character $owner): array
    {
        $adjacent = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        $targets = [];

        foreach ($adjacent as $locationName)
        {
            $characters = $theah->getCharactersAtLocation($locationName);
            foreach ($characters as $character)
            {
                if (! $character->Engaged)
                {
                    continue;
                }

                // WHY: gate the printed "If they have lower Finesse" into eligibility so the
                // once-per-day City Action is not offered as a no-op wound.
                if ($character->ModifiedFinesse >= $owner->ModifiedFinesse)
                {
                    continue;
                }

                $targets[] = $character;
            }
        }

        return $targets;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);

        // City Action: available while Tijani sits at a city location (unmustered or mustered).
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        // WHY: "En Garde City Action" — italic note requires an en garde performer.
        // Not an Engage cost; printed text has no "Engage Tijani" before the bullet.
        if ($owner->Engaged)
        {
            return false;
        }

        return count($this->getEligibleTargets($theah, $owner)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04cd29", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD29)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;

            $targets = $this->getEligibleTargets($game->theah, $owner);
            $args["ids"] = array_map(fn($c) => $c->Id, $targets);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $target): array
    {
        $owner = $this->getOwningCharacter($game->theah);

        $adjacent = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        if (! in_array($target->Location, $adjacent))
        {
            return [false, $game->translate("Character is not at an adjacent City location.")];
        }

        if (! $target->Engaged)
        {
            return [false, $game->translate("Character is not engaged.")];
        }

        if ($target->ModifiedFinesse >= $owner->ModifiedFinesse)
        {
            return [false, $game->translate("Character does not have lower Finesse than Tijani.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD29)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($id);

            if ($target === null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$valid, $message] = $this->isValidTargetForAbility($game, $target);
            if (! $valid)
            {
                throw new UserException($message);
            }

            // WHY: Finesse re-check at resolve — board can change between picker and confirm.
            if ($target->ModifiedFinesse < $owner->ModifiedFinesse)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $target->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $game->theah->queueEvent($woundEvent);
            }

            $playerId = $owner->ControllerId ?: (int) $game->getActivePlayerId();
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }
}
