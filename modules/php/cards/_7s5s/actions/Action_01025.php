<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01025_Burden;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01025 extends RiskAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip Fate's Burden to a character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }


        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega"));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Sorcerer") && $character->hasTrait("Strega"));
        return $characters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01025", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01025)
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

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01025)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot equip Fate's Burden to your own character."));
            }

            if ($character->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("You cannot equip Fate's Burden to a character that is at a different location."));
            }

            //Place original card in special hiding location
            $owner = $this->getOwningCard($game->theah);
            $deck = $game->getGameDeckObject();
            $deck->moveCard($owner->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $owner->Id);
            $game->theah->queueEvent($moveEvent);

            $className = "01025_Burden";
            $playerId = $performer->ControllerId;
            $location = $character->Location;
            $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$className}', $playerId, '$location', $playerId)";
            $game->DbQuery($sql);    
            $id = $game->DbGetLastId();

            $card = $game->instantiateCard($className, $id);
            $card->OwnerId = $playerId;
            $card->ControllerId = $playerId;
            $card->Location = $character->Location;

            if ($card instanceof _01025_Burden)
            {
                $card->OriginalCardId = $owner->Id;
            }
            $game->updateCardObjectInDb($card);

            $event = EventFactory::createAttachmentEquippedEvent($playerId, $character->Id, $card->Id, 0, 0, $asAction = false);
            $game->theah->queueEvent($event);

            $this->announceAction($game);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
