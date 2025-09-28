<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01058 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Opposing Character Home Engaged");
        $this->RequiresPerformerSelected = true;
    }

    private function getAvailablePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $availablePerformers = [];
        foreach ($performers as $performer)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($performer->Location);
            $opposingCharacters = array_values(array_filter($opposingCharacters, fn($opposingCharacter) => 
                            $opposingCharacter->isNotControlledByPlayer($playerId) && 
                            $opposingCharacter->ModifiedCombat < $performer->ModifiedCombat &&
                            ! $opposingCharacter instanceof Leader));     

            if (count($opposingCharacters) > 0)
                $availablePerformers[] = $performer;
        }

        return $availablePerformers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $performers = $this->getAvailablePerformers($playerId, $theah);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getAvailablePerformers($playerId, $theah);        
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01058", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01058)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $opposingCharacters = array_values(array_filter($characters, fn($character) => 
                            $character->isNotControlledByPlayer($performer->ControllerId) && 
                            $character->ModifiedCombat < $performer->ModifiedCombat &&
                            ! $character instanceof Leader));

            $args['characterIds'] = array_map(fn($character) => $character->Id, $opposingCharacters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01058)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($target instanceof Leader)
            {
                throw new \BgaUserException($game->translate("Cannot move a Leader HOME"));
            }
            
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($target->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the performer"));
            }

            if ($target->ModifiedCombat >= $performer->ModifiedCombat)
            {
                throw new \BgaUserException($game->translate("Character has an equal or higher Combat stat than the performer"));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to move ${character_inject_code} HOME.'), [
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            $owner = $this->getOwningCard($game->theah);
            $moveEvent = EventFactory::createCardMovedEvent($performer->ControllerId, $target->Id, $target->Location, Game::LOCATION_PLAYER_HOME, $engage = true, $owner->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState();
        }
    }
}