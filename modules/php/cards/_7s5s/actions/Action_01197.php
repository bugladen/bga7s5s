<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01197 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Equipped Attachment");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $kalla = $this->getOwningCharacter($theah);

        if (! $kalla->isControlled())
        {
            return false;
        }

        //There has to be at least two other friendly characters at her location with an attachment
        $characters = $theah->getCharactersAtLocation($kalla->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId == $kalla->ControllerId && count($character->Attachments) > 0);
        if (count($characters) < 1)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01197", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01197)
        {
            $kalla = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $kalla->Id;

            $characters = $game->theah->getCharactersAtLocation($kalla->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId == $kalla->ControllerId && count($character->Attachments) > 0));
            $characters = array_map(fn($character) => $character->Id, $characters);
            $args["targetCharacterIds"] = $characters;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01197_2)
        {
            $kalla = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $kalla->Id;

            $chosenCharacterId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $chosenCharacter = $game->theah->getCharacterById($chosenCharacterId);
            $args["chosenCharacterId"] = $chosenCharacterId;

            foreach ($chosenCharacter->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment == null)
                {
                    continue;
                }

                $item = [
                    "id" => $attachment->Id,
                    "name" => $attachment->Name,
                ];
                $args["attachments"][] = $item;
            }
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01197_3)
        {
            $kalla = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $kalla->Id;

            $chosenCharacterId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args["chosenCharacterId"] = $chosenCharacterId;

            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->theah->getAttachmentById($chosenAttachmentId);
            $args["attachmentName"] = $chosenAttachment->Name;

            $characters = $game->theah->getCharactersAtLocation($kalla->Location);
            $characters = array_values(array_filter($characters, fn($character) => 
                $character->Id != $chosenCharacterId &&
                $character->ControllerId == $kalla->ControllerId
            )); 

            $args["targetCharacterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01197)
        {
            $kalla = $this->getOwningCharacter($game->theah);
            
            $targetCharacter = $game->theah->getCharacterById($id);
            if ($targetCharacter == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }
    
            if ($targetCharacter->ControllerId != $kalla->ControllerId)
            {
                throw new \BgaUserException($game->translate("Target character is not a friendly character."));
            }
    
            if ($targetCharacter->Location != $kalla->Location)
            {
                throw new \BgaUserException($game->translate("Target character is not at Kalla's location."));
            }

            if (count($targetCharacter->Attachments) == 0)
            {
                throw new \BgaUserException($game->translate("Target character does not have any attachments."));
            }
    
            $game->globals->set(Game::CHOSEN_PERFORMER, $id);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01197_2)
        {
            $kalla = $this->getOwningCharacter($game->theah);

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid attachment id: %d"), $id));
            }

            $chosenCharacterId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $chosenCharacter = $game->theah->getCharacterById($chosenCharacterId);
            if ( ! in_array($attachment->Id, $chosenCharacter->Attachments))
            {
                throw new \BgaUserException(sprintf($game->translate("Attachment %s is not equipped on %s"), $attachment->Name, $chosenCharacter->Name));
            }

            $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01197_3)
        {
            $kalla = $this->getOwningCharacter($game->theah);
    
            $targetCharacter = $game->theah->getCharacterById($id);
            if ($targetCharacter == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }
    
            if ($targetCharacter->ControllerId != $kalla->ControllerId)
            {
                throw new \BgaUserException($game->translate("Target character is not a friendly character."));
            }
    
            if ($targetCharacter->Location != $kalla->Location)
            {
                throw new \BgaUserException($game->translate("Target character is not at Kalla's location."));
            }

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $fromCharacterId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $fromCharacter = $game->theah->getCharacterById($fromCharacterId);
            $chosenCharacter = $game->theah->getCharacterById($id);

            $attachmentId = $game->globals->get(Game::CHOSEN_CARD);

            $moveAttachment = EventFactory::createAttachmentMovedEvent($fromCharacter->ControllerId, $attachmentId, $fromCharacter->Id, $chosenCharacter->Id);
            $game->theah->eventCheck($moveAttachment);
            $game->theah->queueEvent($moveAttachment);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($kalla->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
