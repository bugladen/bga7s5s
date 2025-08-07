<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01055 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Move Opposing Charater to Adjacent Location";
        $this->RequiresPerformerSelected = true;
    }

    private function getValidPerformers(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityByPlayerId($playerId);

        $validPerformers = [];
        foreach ($characters as $character)
        {
            $weapons = 0;
            foreach ($character->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if ($attachment->hasTrait("Weapon") && $attachment->hasTrait("Ranged"))
                {
                    $weapons++;
                }
            }

            $opposingCharacters = $theah->getCharactersAtLocation($character->Location);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => $character->isNotControlledByPlayer($playerId));

            if ($weapons > 0 && (count($opposingCharacters) > 0))
            {
                $validPerformers[] = $character;
            }
        }

        return $validPerformers;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $validPerformers = $this->getValidPerformers($playerId, $theah);

        return count($validPerformers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getValidPerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01055", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01055)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
    
            $args["performerId"] = $performer->Id;

            $characters = $game->theah->getCardObjectsAtLocation($performer->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->isNotControlledByPlayer($performer->ControllerId)));

            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01055_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
    
            $args["performerId"] = $performer->Id;

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $args["targetId"] = $target->Id;

            $args["locationIds"] = $game->theah->getAdjacentCityLocations($target->Location);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01055)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found."));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($target->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot move one of your own characters."));
            }

            if ($performer->Location != $target->Location)
            {
                throw new \BgaUserException($game->translate("You cannot move a character that is at a different location."));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);

            $game->gamestate->nextState("characterChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state === States::HIGH_DRAMA_PLAYER_TURN_01055_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);

            $location = $ids[0];
            $locations = $game->theah->getAdjacentCityLocations($target->Location);
            if (!in_array($location, $locations))
            {
                throw new \BgaUserException(sprintf($game->translate("Location %s is not adjacent to %s."), $location, $target->Location));
            }

            $owner = $this->getOwningCard($game->theah);
            $moveEvent = EventFactory::createCardMovedEvent($performer->ControllerId, $target->Id, $target->Location, $location, $engage = false, $owner->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} chose to move ${target_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "target_inject_code" => $target->getInjectCode(),
                "location_name" => $location
            ]);

            $game->gamestate->nextState("locationChosen");
        }
    }   

}