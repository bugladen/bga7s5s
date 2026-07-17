<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03054 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound unequipped performer; Pressure with Resolve");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return Character[]
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $eligible = [];
        foreach ($performers as $performer)
        {
            if (count($performer->Attachments) > 0)
            {
                continue;
            }

            if (! $performer->canPressure(Game::STAT_RESOLVE))
            {
                continue;
            }

            $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $playerId);
            if (count($opposing) === 0)
            {
                continue;
            }

            $eligible[] = $performer;
        }

        return $eligible;
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
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            if (count($performer->Attachments) > 0)
            {
                throw new UserException($game->translate("Performer must be unequipped."));
            }

            if (! $performer->canPressure(Game::STAT_RESOLVE))
            {
                throw new UserException($game->translate("Performer cannot pressure with Resolve."));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer is not in the City."));
            }

            $location = $performer->Location;

            $game->globals->set(Game::PRESSURING_PLAYER, $performer->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_RESOLVE);
            $game->globals->set(Game::CHOSEN_LOCATION, $location);
            // Preserve performer id for the post-pressure target pick UI.
            $game->globals->set(Game::CHOSEN_CARD, $performerId);
            // WHY: Wound may destroy the performer (locker) before stHighDramaPressureLocation.
            // That state reads Location from CHOSEN_PERFORMER when non-zero — clear it so
            // pressure uses CHOSEN_LOCATION (the city slot captured above) instead.
            $game->globals->set(Game::CHOSEN_PERFORMER, 0);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $performerId,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $event->theah->queueEvent($woundEvent);

            $pressureStats = $event->theah->getPressureStats($performer, $location, Game::STAT_RESOLVE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent(
                $performer->ControllerId,
                $performerId,
                $location,
                $pressureStats
            );
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent(
                $performer->ControllerId,
                $owner->Id,
                "pressureLocation",
                $this->Id
            );
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            if ($event->success)
            {
                $opposing = $event->theah->getOpposingCharactersAtLocation($event->location, $owner->ControllerId);
                if (count($opposing) > 0)
                {
                    $transitionEvent = EventFactory::createTransitionEvent(
                        $owner->ControllerId,
                        $owner->Id,
                        "03054",
                        $this->Id
                    );
                    $event->theah->queueEvent($transitionEvent);
                    return;
                }

                $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: there is no opposing character to wound.'), [
                    "scheme_inject_code" => $owner->getInjectCode(),
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03054)
        {
            $owner = $this->getOwningCard($game->theah);
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $performerId = (int)$game->globals->get(Game::CHOSEN_CARD, 0);

            $args['performerId'] = $performerId;
            $args['location'] = $location;

            $opposing = $game->theah->getOpposingCharactersAtLocation($location, $owner->ControllerId);
            $args['ids'] = array_values(array_map(fn($character) => $character->Id, $opposing));
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        $location = $game->globals->get(Game::CHOSEN_LOCATION);

        if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be an opposing character")];
        }

        if ($character->Location != $location)
        {
            return [false, $game->translate("Character is not at the pressured location")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03054)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            $fromLocation = $target->Location;

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $target->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            // WHY: Skip Home move if this wound will destroy them — otherwise a later
            // EventCardMoved could yank them from the locker back to Home.
            $willDie = ($target->Wounds + 1 >= $target->ModifiedResolve);
            if (! $willDie)
            {
                $moveEvent = EventFactory::createCardMovingEvent(
                    $owner->ControllerId,
                    $target->Id,
                    $fromLocation,
                    Game::LOCATION_PLAYER_HOME,
                    false,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($moveEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
