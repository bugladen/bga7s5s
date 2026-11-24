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
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05DabneyUS01 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Move to Adjacent Location; Issue Combat Challenge (Non-Leader)";
    }

    /**
     * Challengeable opposing non-Leaders at a city location (Combat legality included).
     *
     * @return list<Character>
     */
    private function getChallengeTargetsAtLocation(Theah $theah, Character $owner, string $location): array
    {
        $characters = $theah->getCharactersAtLocation($location);
        return array_values(array_filter(
            $characters,
            fn($c) => $c->isNotControlledByPlayer($owner->ControllerId)
                && ! $c->hasTrait("Leader")
                && $theah->challengeCanBeIssued($owner->Id, $c->Id, Game::STAT_COMBAT)
        ));
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
        // WHY no challenge-target gate: printed L→R — move always; challenge only if a
        // valid opposing non-Leader exists at the destination (Yepikhodov / pattern-c).
        $adjacentLocations = $theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
        return count($adjacentLocations) > 0;
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

        // WHY: Hub-exit after the move actually lands. Always enter the GAME bridge
        // (05DabneyUS01_3) so Soline / CardMoved reactions (pri 6) drain before the
        // challenge-target check (stateFromAction). Checking here with toLocation would
        // race those reactions and soft-lock an empty picker.
        if ($event instanceof EventCardMoved && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner == null || $event->cardId != $owner->Id)
            {
                return;
            }

            $event->theah->deleteQueuedTransitionsNamed("05DabneyUS01_3");
            $event->theah->deleteQueuedTransitionsNamed("05DabneyUS01_2");

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "05DabneyUS01_3", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Come Hither / Amour / Vittoria — gate challenge on EventCharacterTargeted
        // surviving. Target pick is AFTER the move, so cancel must not undo/delete the
        // move batch (move already drained). Only the challenge hub-exit is queued here.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCharacter($event->theah);
            $target = $event->theah->getCharacterById($event->targetId);
            if ($target == null)
            {
                return;
            }

            // Sync after Vittoria/DI redirect so challenge defender matches.
            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            // WHY: Dedicated type — not unrefusable (refuse allowed). Kept so ActivateTechnique
            // Back stays blocked after the move lands. Do not reuse VALERI_MIKHAILOV
            // (no-intervene) or STAND_YOUR_GROUND (scheme-owned / unrefusable). Keep OFF
            // stIssueChallenge auto-engage list (no Engage printed).
            $game->globals->set(Game::CHALLENGE_TYPE, Game::VALERI_CHALLENGE_TYPE);

            $event->theah->deleteQueuedTransitionsNamed("05DabneyUS01_2");
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
            $args['locationIds'] = array_values(
                $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false)
            );
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01_4)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;

            $targets = $this->getChallengeTargetsAtLocation($game->theah, $owner, $owner->Location);
            // WHY: array_values so JSON is a dense array — associative keys become objects and break forEach.
            $args['ids'] = array_values(array_map(fn($c) => $c->Id, $targets));
        }

        return $args;
    }

    // WHY: GAME bridge after move + CardMoved reactions. Location is committed;
    // challenge only if a legal opposing non-Leader remains (L→R fail-forward).
    public function stateFromAction(Game $game, int $state, string $stateName): void
    {
        parent::stateFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01_3)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $targets = $this->getChallengeTargetsAtLocation($game->theah, $owner, $owner->Location);
            if (count($targets) > 0)
            {
                $game->gamestate->nextState("hasTargets");
                return;
            }

            $resolved = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($resolved);
            $game->theah->runEvents(true);
            $game->gamestate->nextState("noTargets");
        }
    }

    // WHY: Same gate as Andriana Action_02001. "05DabneyUS01_2" is TRANSITION_PRIORITY (9),
    // so CardMoved reactions (Soline Reaction_03040, pri 6) resolve first and can yank the
    // target off Valeri's landing spot before TECHNIQUE_AVAILABLE. stTechniqueAvailable
    // calls this and challengeSkipped when false — challenge requires same location.
    // Also re-check ChallengeIssued legality (Diplomatic Envoy etc.): Valeri always has a
    // Technique, so an illegal issue would soft-lock on ActivateTechnique with Back blocked.
    public function shouldIssueChallenge(Game $game): bool
    {
        $owner = $this->getOwningCharacter($game->theah);
        $targetId = (int) $game->globals->get(Game::CHOSEN_TARGET);
        $target = $game->theah->getCharacterById($targetId);

        if ($owner === null || $target === null)
        {
            return false;
        }

        if ($target->Location !== $owner->Location)
        {
            return false;
        }

        return $game->theah->challengeCanBeIssued($owner->Id, $target->Id);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCharacter($game->theah);
        if ($character->ControllerId == $owner->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, "Target must be a non-Leader character."];
        }

        // WHY: Post-move challenge pick — target must share Valeri's current location
        // (pre-move adjacent gate no longer applies; destination already chosen).
        if ($character->Location !== $owner->Location)
        {
            return [false, "Target character is not at Valeri's location"];
        }

        if (! $game->theah->challengeCanBeIssued($owner->Id, $character->Id, Game::STAT_COMBAT))
        {
            return [false, "That character cannot be issued a Combat challenge."];
        }

        return [true, ""];
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01)
        {
            $location = $ids[0] ?? '';
            $owner = $this->getOwningCharacter($game->theah);
            $adjacent = $game->theah->getAdjacentCityLocations($owner->Location, $includeHome = false);
            if (! in_array($location, $adjacent, true))
            {
                throw new UserException("Must move to an adjacent City location.");
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);

            // WHY: Engage not printed — move without engaging (contrast base Valeri 01123 engage=true).
            // Challenge transition (or ActionResolved) is queued from EventCardMoved.
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $owner->Id,
                $owner->Location,
                $location,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05DABNEYUS01_4)
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

            // WHY: Fire targeting alone here. Challenge hub-exit is queued from handleEvent
            // only when EventCharacterTargeted is not canceled. Move already happened —
            // cancel must not delete a move batch. batchId still lets UL / Maryam / Vittoria
            // strip only the targeting follow-up on Pass/Decline.
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $batchId;
            $game->theah->queueEvent($targetedEvent);

            // createActionResolvedEvent() is queued by the challenge resolution flow,
            // or by stTechniqueAvailable → challengeSkipped when shouldIssueChallenge fails,
            // or by stateFromAction (05DabneyUS01_3) when no challenge targets remain.

            $game->gamestate->nextState("opponentChosen");
        }
    }
}
