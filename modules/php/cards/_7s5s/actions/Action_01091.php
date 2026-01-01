<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01091 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Heal Wounds");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => $character->Wounds > 0);

        return count($characters) > 0;        
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01091", $this->Id);
            $event->theah->queueEvent($transition);            
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01091)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;
            
            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->Wounds > 0));
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01091_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;

            $args["ids"] = $game->globals->get(Game::CHOSEN_TARGET);
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01091)
        {
            foreach ($ids as $id)
            {
                $character = $game->theah->getCharacterById($id);
                if ($character == null)
                {
                    throw new \BgaUserException($game->translate("Invalid character id: %d"), $id);
                }

                $owner = $this->getOwningCharacter($game->theah);
                if ($character->Location != $owner->Location)
                {
                    throw new \BgaUserException($game->translate("Character is not at the same location as \"Madre\" Dolores."));
                }

                if ($character->Wounds == 0)
                {
                    throw new \BgaUserException($game->translate("Character is not wounded."));
                }
            }

            if (count($ids) == 1)
            {
                $healEvent = EventFactory::createCharacterHealedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($healEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $this->setUsed($game->theah, true);
                $this->announceAction($game);
                $this->resetPlayerPassCount($game);

                $game->gamestate->nextState("characterChosen");
            }
            else if (count($ids) == 2)
            {
                $game->globals->set(Game::CHOSEN_TARGET, $ids);
                $game->gamestate->nextState("charactersChosen");
            }
        }
    }
    
    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01091_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            $hand = array_filter($hand, fn($card) => $card->Id == $id);
            if (count($hand) == 0)
            {
                throw new \BgaUserException($game->translate("Card is not in your hand."));
            }

            $card = $game->getCardObjectFromDb($id);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $owner->Id, false, false, false);
            $game->theah->queueEvent($discardEvent);

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);
            
            $ids = $game->globals->get(Game::CHOSEN_TARGET);
            foreach ($ids as $id)
            {
                $character = $game->theah->getCharacterById($id);
                $event = EventFactory::createCharacterHealedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($event);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardChosen");
        }
    }
}
