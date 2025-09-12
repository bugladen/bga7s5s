<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01012 extends CharacterAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Sibella, Wound Opposing Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $sibella = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($sibella))
            return false;

        if (! $sibella->hasTrait("Sorcerer"))
            return false;

        $characters = $theah->getOpposingCharactersAtLocation($sibella->Location, $sibella->ControllerId);
        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $sibella = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($sibella->ControllerId, $sibella->Id, "01012", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01012)
        {
            $performer = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $performer->Id;

            $characters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $args["characterIds"] = array_map(fn($c) => $c->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01012)
        {
           $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $performer = $this->getOwningCharacter($game->theah);

            if ($target->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character not at the same location"));
            }

            $this->announceAction($game);

            $event = EventFactory::createCharacterWoundedEvent($performer->Id, $performer->Id, 1, $performer->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createCharacterWoundedEvent($target->Id, $performer->Id, 1, $performer->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $owner = $this->getOwningCharacter($game->theah);
            $event = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $target->Id, $target->Location);
            $game->theah->queueEvent($event);

            $this->setUsed($game->theah, true);
            $game->gamestate->nextState("opposingCharacterChosen");
        }
    }

}