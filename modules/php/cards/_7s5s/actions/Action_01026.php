<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01026 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Performer, Manipulate Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Red Hand"));

        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait("Red Hand")));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01026", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01026)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01026)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the performer"));
            }

            $owner = $this->getOwningCard($game->theah);

            $performer->unEquipAllAttachments($game->theah);
            $destroyedEvent = EventFactory::createCharacterDestroyedEvent($performer->ControllerId, $performer->Id, $owner->getInjectCode());
            $game->theah->queueEvent($destroyedEvent);

            if (! $character->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $character->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }
            else
            {
                $moveEvent = EventFactory::createCardMovingEvent($performer->ControllerId, $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
                $game->theah->queueEvent($moveEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
