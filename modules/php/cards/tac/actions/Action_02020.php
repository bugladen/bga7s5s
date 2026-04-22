<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02020 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Manipulate a Non-Leader Character");
        $this->RequiresPerformerSelected = true;
    }

    private function getPerformers(int $playerId, Theah $theah): array
    {
        $availablePerformers = [];
        $performers = $theah->getcharactersInCityByPlayerId($playerId);
        foreach ($performers as $performer)
        {
            $opposingCharacters = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => ! $character->hasTrait("Leader") && ! $character->Engaged);
            if (count($opposingCharacters) == 0)
            {
                continue;
            }

            foreach ($performer->Attachments as $attachmentId)
            {
                $attachment = $theah->getAttachmentById($attachmentId);
                if ($attachment && (($attachment->hasTrait("Weapon") && $attachment->hasTrait("Melee")) || $attachment->hasTrait("Eisenfaust")))
                {
                    $availablePerformers[$performer->Id] = $performer;
                    break;
                }
            }
        }

        return array_values($availablePerformers);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $this->getPerformers($playerId, $theah);

        return count($performers) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getPerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02020", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020)
        {
            $owner = $this->getOwningCard($game->theah);
            $args['yield'] = $owner->getInjectCode();

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $opposingCharacters = $game->theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
            $opposingCharacters = array_filter($opposingCharacters, fn($character) => ! $character->hasTrait("Leader") && ! $character->Engaged);
            $args['ids'] = array_map(fn($character) => $character->Id, array_values($opposingCharacters));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $args['attachments'] = [];
            foreach ($performer->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment && (($attachment->hasTrait("Weapon") && $attachment->hasTrait("Melee")) || $attachment->hasTrait("Eisenfaust")))
                {
                    $args['attachments'][] = [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name,
                    ];
                }
            }

            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);
            $args['characterId'] = $characterId;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_3)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performerId;

            $args['characterId'] = $game->globals->get(Game::CHOSEN_CARD);
            $args['character'] = $game->theah->getCharacterById($args['characterId']);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($character->hasTrait("Leader"))
            {
                throw new UserException($game->translate("Character is a Leader"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($character->ControllerId == $performer->ControllerId)
            {
                throw new UserException($game->translate("Character is the same controller as the performer"));
            }

            if ($character->Location != $performer->Location)
            {
                throw new UserException($game->translate("Character is not at the same location as the performer"));
            }

            if ($character->Engaged)
            {
                throw new UserException($game->translate("Character is already engaged"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $character->Id);

            $game->gamestate->nextState("characterChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_2)
        {
            $attachment = $game->theah->getAttachmentById($id);

            if ($attachment == null)
            {
                throw new UserException($game->translate("Attachment not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($attachment->AttachedToId != $performer->Id)
            {
                throw new UserException($game->translate("Attachment is not attached to the performer"));
            }

            if ($attachment->Engaged)
            {
                throw new UserException($game->translate("Attachment is already engaged"));
            }

            if (! (($attachment->hasTrait("Weapon") && $attachment->hasTrait("Melee")) || $attachment->hasTrait("Eisenfaust")))
            {
                throw new UserException($game->translate("Attachment is not a Melee Weapon or Eisenfaust"));
            }

            $owner = $this->getOwningCard($game->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $attachment->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            $transitionEvent = EventFactory::createTransitionEvent($character->ControllerId, $owner->Id, "02020_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $this->setUsed($game->theah, true);
            $this->announceAction($game);
            $this->resetPlayerPassCount($game);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("attachmentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02020_3)
        {
            if ($id != 1 && $id != 2)
            {
                throw new UserException($game->translate("Invalid action"));
            }

            $owner = $this->getOwningCard($game->theah);
            $characterId = $game->globals->get(Game::CHOSEN_CARD);
            $character = $game->theah->getCharacterById($characterId);

            // Engage
            if ($id == 1)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }

            // Wound
            if ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${owner_inject_code}: ${player_name} declined to engage ${character_inject_code}'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($character->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }

            $game->gamestate->nextState();
        }
    }
}