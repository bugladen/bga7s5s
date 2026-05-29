<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02051 extends RiskCityAction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Performer, En Garde Target Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $sorcerers = array_filter($characters, fn($c) => $c->hasTrait("Sorcerer") && !$c->Engaged);

        foreach ($sorcerers as $sorcerer)
        {
            $atLocation = $theah->getCharactersAtLocation($sorcerer->Location);
            $targets = array_filter($atLocation, fn($c) => $c->Id != $sorcerer->Id && $c->Engaged);
            if (count($targets) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $sorcerers = array_values(array_filter($performers, fn($c) => $c->hasTrait("Sorcerer") && !$c->Engaged));

        $eligible = [];
        foreach ($sorcerers as $sorcerer)
        {
            $atLocation = $theah->getCharactersAtLocation($sorcerer->Location);
            $targets = array_filter($atLocation, fn($c) => $c->Id != $sorcerer->Id && $c->Engaged);
            if (count($targets) > 0)
            {
                $eligible[] = $sorcerer;
            }
        }

        return $eligible;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "02051", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02051)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $atLocation = $game->theah->getCharactersAtLocation($performer->Location);
            $targets = array_values(array_filter($atLocation, fn($c) => $c->Id != $performerId && $c->Engaged));
            $args["ids"] = array_map(fn($c) => $c->Id, $targets);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performerId)
        {
            return [false, $game->translate("You cannot target the performer.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the performer's location.")];
        }

        if (!$character->Engaged)
        {
            return [false, $game->translate("Character is not engaged.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02051)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($game->theah);

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
            $game->theah->queueEvent($sorceryStartEvent);

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $engardeEvent = EventFactory::createCardEngardedEvent($target->ControllerId, $target->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            $lockerEvent = EventFactory::createCardSentToLockerEvent($owner->ControllerId, $owner->Id);
            $game->theah->queueEvent($lockerEvent);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId, $target->Id, $target->Location);
            $game->theah->queueEvent($sorceryPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
