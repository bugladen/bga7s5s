<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01194 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound Target Opposing Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $adelheide = $this->getOwningCharacter($theah);

        if (! $adelheide->isControlled())
        {
            return false;
        }

        //She has to be in the city
        if (! $theah->cardInCity($adelheide))
        {
            return false;
        }

        //She has to have an attachment
        if (count($adelheide->Attachments) == 0)
        {
            return false;
        }

        //She has to have an opposing character at her location
        $characters = $theah->getCharactersAtLocation($adelheide->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId != $adelheide->ControllerId);
        if (count($characters) == 0)
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01194", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01194)
        {
            $adelheide = $this->getOwningCharacter($game->theah);
            $args["characterId"] = $adelheide->Id;

            $attachments = [];
            foreach ($adelheide->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment == null)
                {
                    continue;
                }

                $attachmentItem = [
                    "id" => $attachmentId,
                    "name" => $game->translate($attachment->Name)
                ];
                $attachments[] = $attachmentItem;
            }
            $args["attachments"] = $attachments;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01194_2)
        {
            $adelheide = $this->getOwningCharacter($game->theah);
            $args["characterId"] = $adelheide->Id;

            $characters = $game->theah->getCharactersAtLocation($adelheide->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId != $adelheide->ControllerId));
            $characterIds = array_map(fn($character) => $character->Id, $characters);
            $args["targetCharacterIds"] = $characterIds;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01194)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid attachment id: %d"), $id));
            }

            $adelheide = $this->getOwningCharacter($game->theah);
            if ( ! in_array($id, $adelheide->Attachments))
            {
                throw new \BgaUserException(sprintf($game->translate("Adelheide does not have this attachment: %d"), $id));
            }

            $game->globals->set(Game::CHOSEN_CARD, $id);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01194_2)
        {
            $adelheide = $this->getOwningCharacter($game->theah);

            $targetCharacter = $game->theah->getCharacterById($id);
            if ($targetCharacter == null)
            {
                throw new \BgaUserException(sprintf($game->translate("Invalid target character id: %d"), $id));
            }

            if ($targetCharacter->ControllerId == $adelheide->ControllerId)
            {
                throw new \BgaUserException($game->translate("Adelheide cannot wound a friendly character."));
            }

            if ($targetCharacter->Location != $adelheide->Location)
            {
                throw new \BgaUserException($game->translate("Target character is not at Adelheide's location."));
            }

            //Unequip the attachment
            $attachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($attachment->ControllerId, $adelheide->Id, $attachmentId);
            $game->theah->eventCheck($unequipEvent);

            //Discard the attachment
            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($attachment->ControllerId, $attachmentId, $attachment->Location);
            $game->theah->eventCheck($discardEvent);

            //Wound the target character
            $woundEvent = EventFactory::createCharacterWoundedEvent($targetCharacter->Id, $adelheide->Id, 1, $adelheide->getInjectCode());
            $game->theah->eventCheck($woundEvent);

            $game->theah->queueEvent($unequipEvent);
            $game->theah->queueEvent($discardEvent);
            $game->theah->queueEvent($woundEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($adelheide->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->notify->all('message', clienttranslate('${player_name} uses Adelheide Schmidt\'s Action to wound ${character_name}.'), [
                'i18n' => ['character_name'],
                'player_name' => $game->getActivePlayerName(),
                'character_name' => $targetCharacter->Name,
            ]);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState("characterChosen");
        }
    }
}
