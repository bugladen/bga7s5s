<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01156 extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Discard Card, Wound Adjacent Opposing Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $perfomer = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($perfomer))
            return false;

        $adjacentLocations = $theah->getAdjacentCityLocations($perfomer->Location, $includeHome = false);
        foreach ($adjacentLocations as $adjacentLocation)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
            $opposingCharacter = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($playerId));
            if (count($opposingCharacter) > 0)
                return true;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningAttachment($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01156", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $performer = $this->getOwningCharacter($game->theah);
            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $game->theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacter = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($performer->ControllerId));
                if (count($opposingCharacter) > 0)
                    $charactersIds[] = $opposingCharacter[0]->Id;
            }

            $charactersIds = array_unique($charactersIds);
            $args['charactersIds'] = $charactersIds;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_3)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $args['targetId'] = $targetId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156)
        {
            $performer = $this->getOwningCharacter($game->theah);

            $card = $game->getCardObjectFromDb($id);

            if ($card->ControllerId != $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Card not in your hand"));
            }

            $deck = $game->getGameDeckObject();
            $deck->moveCard($card->Id, Game::LOCATION_PURGATORY);
            $game->globals->set(Game::CHOSEN_CARD, $card->Id);
            
            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_2)
        {
            $target = $game->theah->getCharacterById($id);

            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $performer = $this->getOwningCharacter($game->theah);

            if ($target->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot target your own character"));
            }

            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            if (! in_array($target->Location, $adjacentLocations))
            {
                throw new \BgaUserException($game->translate("Target is not adjacent to the performer"));
            }

            $musket = $this->getOwningAttachment($game->theah);
            if ($target->Engaged)
            {
                $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the action of ${musket_inject_code} and selected ${character_inject_code} as the target, who was already Engaged.'), [
                    "player_name" => $game->getPlayerNameById($performer->ControllerId),
                    "character_inject_code" => $target->getInjectCode(),
                    "musket_inject_code" => $musket->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterWoundedEvent($target->Id, $musket->Id, 1, $musket->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }
            else
            {
                $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
                $game->notifyAllPlayers("message", clienttranslate('${player_name} has used the action of ${musket_inject_code} and selected ${character_inject_code} as the target.
                ${target_player_name} must decide to Engage or suffer a wound.'), [
                    "player_name" => $game->getPlayerNameById($performer->ControllerId),
                    "target_player_name" => $game->getPlayerNameById($target->ControllerId),
                    "character_inject_code" => $target->getInjectCode(),
                    "musket_inject_code" => $musket->getInjectCode(),
                ]);
    
                $transition = EventFactory::createTransitionEvent($target->ControllerId, $musket->Id, "01156_3", $this->Id);
                $game->theah->queueEvent($transition);
            }

            $this->setUsed($game->theah, true);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_3)
        {
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            $musket = $this->getOwningAttachment($game->theah);

            //Engage
            if ($id == 1)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($target->ControllerId, $target->Id, $musket->Id);
                $game->theah->queueEvent($engageEvent);
            }

            //Wound
            if ($id == 2)
            {
                $woundEvent = EventFactory::createCharacterWoundedEvent($target->Id, $musket->Id, 1, $musket->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }

            $game->gamestate->nextState();
        }
    }
}