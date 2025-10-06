<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01092 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Opposing Engaged Character Home");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $makepeace = $this->getOwningCharacter($theah);

        $opposingCharacters = $theah->getCharactersAtLocation($makepeace->Location);
        $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId) && $character->Engaged);
        $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->ModifiedInfluence <= $makepeace->ModifiedInfluence);
        
        return count($opposingCharacters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01092", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01092)
        {
            $makepeace = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $makepeace->Id;

            $opposingCharacters = $game->theah->getCharactersAtLocation($makepeace->Location);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($makepeace->ControllerId) && $character->Engaged);
            $opposingCharacters = array_values(array_filter($opposingCharacters, fn($character) => $character->ModifiedInfluence <= $makepeace->ModifiedInfluence));
            $args['ids'] = array_map(fn($character) => $character->Id, $opposingCharacters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01092)
        {
            $character = $game->theah->getCharacterById($id);

            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $makepeace = $this->getOwningCharacter($game->theah);

            if ($character->Location != $makepeace->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as Makepeace Botwighte"));
            }

            if ($character->ControllerId == $makepeace->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }

            if (! $character->Engaged)
            {
                throw new \BgaUserException($game->translate("Character is not engaged"));
            }

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $moveEvent = EventFactory::createCardMovedEvent($makepeace->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, $engage = false, $makepeace->Id);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
