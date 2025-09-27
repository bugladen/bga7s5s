<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01044 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate("Manipulate Opposing Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $performers = $this->getAvailablePerformers($playerId, $theah);
        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        $performers += $this->getAvailablePerformers($playerId, $theah);

        return $performers;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044 || 
            $state == States::HIGH_DRAMA_PLAYER_TURN_01044_2 ||
            $state == States::HIGH_DRAMA_PLAYER_TURN_01044_3)
        {
            $actionCardId = $game->globals->get(Game::CHOSEN_ACTION);
            $actionCard = $game->theah->getInPlayActionById($actionCardId);

            $id = Game::THEAH_ID;
            if ($actionCard instanceof CardAction)
                $id = $actionCard->OwnerId;

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
    
            $args['performerId'] = $performer->Id;
            $args['actionCardId'] = $id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044)
        {
            $attachments = [];
            foreach ($performer->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if (! $attachment->Engaged)
                {
                    $attachments[] = [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name
                    ];
                }
            }
    
            $args['attachments'] = $attachments;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            // Get all characters opposing performer at the same location as the performer with less or equal attachments
            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            $characters = array_filter($characters, fn($character) => $character->isControlled() && $character->ControllerId != $performer->ControllerId);
            $opposingCharacters = [];
            foreach ($characters as $character)
            {
                if (count($character->Attachments) <= count($performer->Attachments))
                {
                    $opposingCharacters[] = $character->Id;
                }
            }
            $args['ids'] = $opposingCharacters;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044_3)
        {
            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);
            $args['opposingCharacterId'] = $character->Id;

            $args['engaged'] = $character->Engaged;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if (! $attachment)
            {
                throw new \BgaUserException($game->translate("Invalid attachment"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if( ! in_array($attachment->Id, $performer->Attachments))
            {
                throw new \BgaUserException($game->translate("Attachment is not equipped to the performer"));
            }

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044_2)
        {
            $character = $game->theah->getCharacterById($id);
            if (! $character)
            {
                throw new \BgaUserException($game->translate("Invalid character"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($character->isControlled() && $character->ControllerId == $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("Character is not opposing the performer"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the performer"));
            }

            if(count($character->Attachments) > count($performer->Attachments))
            {
                throw new \BgaUserException($game->translate("Character has more or equal number of attachments than the performer"));
            }
            
            $game->globals->set(Game::CHOSEN_CARD, $character->Id);

            $game->gamestate->nextState("opposingCharacterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01044_3)
        {
            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            //Chooses to engage the character
            if ($id == 1)
            {
                if ($character->Engaged)
                {
                    throw new \BgaUserException($game->translate("Character is already engaged"));
                }

                $attachmentId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
                $attachment = $game->theah->getAttachmentById($attachmentId);

                $owner = $this->getOwningCard($game->theah);
                $game->notifyAllPlayers("message", clienttranslate('${action_inject_code}: ${player_name} uses Action to Engage ${attachment_inject_code} and ${character_inject_code}'), [
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "action_inject_code" => $owner->getInjectCode(),
                    "attachment_inject_code" => $attachment->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $aam = $this->getOwningCard($game->theah);
                $event = EventFactory::createCardEngagedEvent($game->getActivePlayerId(), $attachment->Id, $aam->Id);
                $game->theah->queueEvent($event);

                $event = EventFactory::createCardEngagedEvent($game->getActivePlayerId(), $character->Id, $aam->Id);
                $game->theah->queueEvent($event);

                $this->setUsed($game->theah, true);
            }

            //Chooses to send the character home
            if ($id == 2)
            {
                $attachmentId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
                $attachment = $game->theah->getAttachmentById($attachmentId);

                $owner = $this->getOwningCard($game->theah);
                $game->notifyAllPlayers("message", clienttranslate('${action_inject_code}: ${player_name} uses Action to Engage ${attachment_inject_code} and send ${character_inject_code} Home'), [
                    "action_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "attachment_inject_code" => $attachment->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $aam = $this->getOwningCard($game->theah);
                $event = EventFactory::createCardEngagedEvent($game->getActivePlayerId(), $attachment->Id, $aam->Id);
                $game->theah->eventCheck($event);
                $game->theah->queueEvent($event);

                $movedHome = EventFactory::createCardMovedEvent($game->getActivePlayerId(), $character->Id, $character->Location, Game::LOCATION_PLAYER_HOME, false);
                $game->theah->eventCheck($movedHome);
                $game->theah->queueEvent($movedHome);

                $this->setUsed($game->theah, true);
            }

            $game->gamestate->nextState("manipulationChosen");
        }
    }

    private function getAvailablePerformers(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);

        $performersWithAttachments = [];
        foreach ($performers as $performer)
        {
            foreach ($performer->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if (! $attachment->Engaged)
                {
                    //Get opposing characters at the same location
                    $opposingAtLocation = $theah->getCharactersAtLocation($performer->Location);
                    $opposingAtLocation = array_filter($opposingAtLocation, fn($opposingCharacter) => $opposingCharacter->isControlled() && $opposingCharacter->ControllerId != $playerId);

                    //Only include opposing characters that have less or equal attachments
                    $opposingWithAttachments = array_filter($opposingAtLocation, fn($opposingCharacter) => count($opposingCharacter->Attachments) <= count($performer->Attachments));

                    if (count($opposingWithAttachments) > 0)
                    {
                        $performersWithAttachments[] = $performer;
                        break;
                    }
                }
            }
        }

        return $performersWithAttachments;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01044", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }
}