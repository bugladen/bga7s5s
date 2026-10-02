<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05DabneyUS01 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Move to Adjacent Location; Issue Unrefusable Combat Challenge";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        // WHY: No Engage / En Garde printed — engaged Valeri remains eligible (trichotomy c).
        if (! $owner->canChallenge($theah))
        {
            return false;
        }

        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        foreach ($adjacentLocations as $adjacentLocation)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
            $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($playerId));
            if (count($opposingCharacters) > 0)
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
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05DabneyUS01", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Come Hither (01162) pattern — gate move+challenge on EventCharacterTargeted
        // surviving. Vittoria / Unyielding Loyalty / Maryam cancel during targeting; queuing
        // move beside the targeting event lets the challenge proceed before the reaction
        // resolves (journal 2026-09-13-12). Also fires "when targeted" *before* technique
        // activation overwrites TRANSITION_INTERNAL_ID (Premonition journal 2026-09-05-07),
        // so Vittoria can react with a Thug still in hand at pick time.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);
            $target = $event->theah->getCharacterById($event->targetId);
            if ($target == null)
            {
                return;
            }

            // Sync after Vittoria/DI redirect so move destination and challenge defender match.
            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            // WHY: Dedicated type — unrefusable. Do not reuse VALERI_MIKHAILOV (no-intervene) or
            // STAND_YOUR_GROUND (scheme-owned). Keep OFF stIssueChallenge auto-engage list (no Engage printed).
            $game->globals->set(Game::CHALLENGE_TYPE, Game::VALERI_CHALLENGE_TYPE);

            // WHY: Engage not printed — move without engaging (contrast base Valeri 01123 engage=true).
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $target->Location,
                false,
                $owner->Id,
                $this->Id
            );
            $event->theah->eventCheck($moveEvent);
            $event->theah->queueEvent($moveEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05DabneyUS01_2", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;

            $adjacentLocations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            $charactersIds = [];
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $game->theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($owner->ControllerId));
                foreach ($opposingCharacters as $opposingCharacter)
                {
                    $charactersIds[] = $opposingCharacter->Id;
                }
            }

            // WHY: array_values so JSON is a dense array — associative keys become objects and break forEach.
            $args['ids'] = array_values($charactersIds);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);
        if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        $locations = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        if (! in_array($character->Location, $locations))
        {
            return [false, "Target character is not at an adjacent location"];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException("Character not found");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);

            // WHY: Fire targeting alone here. Move + challenge transition are queued from
            // handleEvent only when EventCharacterTargeted is not canceled (see above).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($targetedEvent);

            // createActionResolvedEvent() is queued by the challenge resolution flow.

            $game->gamestate->nextState("opponentChosen");
        }
    }
}
