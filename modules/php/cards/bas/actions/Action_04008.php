<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04008 extends RiskAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip Fate's Silence to a character");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        // WHY queryCard: Patricia/Uwe trait fiction only fires when the ability identifies itself.
        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $characters = array_filter(
            $characters,
            fn(Character $character) => $character->hasTrait("Sorcerer", $owner) && $character->hasTrait("Strega", $owner)
        );

        return array_values(array_filter(
            $characters,
            fn(Character $performer) => count($this->getValidTargets($theah, $performer)) > 0
        ));
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $characters = $theah->getCharactersAtLocation($performer->Location);
        return array_values(array_filter(
            $characters,
            fn(Character $character) => $character->isNotControlledByPlayer($performer->ControllerId)
                && ! $character->hasTrait("Leader")
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04008", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Equip only when targeting survives. UL / Terraria / Maryam cancel
        // EventCharacterTargeted; queuing createRiskAttachment beside the hook would
        // equip anyway (same trap as Action_04040 / Action_05Teens / Action_05Shakedown).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($owner === null || $character === null || $performer === null)
            {
                return;
            }

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $character->Id, $character->Location);
            $event->theah->queueEvent($sorceryStartEvent);

            // Mirror Action_01025 — RiskAttachment equip is sync then Equipped event.
            $game->createRiskAttachment($game, "04008_Silence", $owner->Id, $character->Location, $performer->ControllerId, $performer->ControllerId, $character->Id, $this->Id);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);

            $playedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $character->Id, $character->Location);
            $event->theah->queueEvent($playedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04008)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
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

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot equip Fate's Silence to your own character.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("You cannot equip Fate's Silence to a character that is at a different location.")];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("You cannot equip Fate's Silence to a Leader.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04008)
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

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: Cancel hook first. Equip + Sorcerer start/played emit from handleEvent
            // only when EventCharacterTargeted survives (Teens / 04040 / 05Shakedown).
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $character->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
