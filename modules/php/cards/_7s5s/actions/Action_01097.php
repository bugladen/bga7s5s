<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01097 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Engage Character");
    }

    public function getTargetsForAction(int $playerId, Theah $theah): array
    {
        $deck = $theah->game->getGameDeckObject();

        $owner = $this->getOwningCharacter($theah);
        $ownerDeckName = $theah->game->getPlayerFactionDeckName($owner->ControllerId);
        $ownerDeckCount = $deck->countCardsInLocation($ownerDeckName);

        $targets = [];
        $characters = $theah->getOpposingCharactersAtLocation($owner->Location, $playerId);
        foreach ($characters as $character)
        {
            $deckName = $theah->game->getPlayerFactionDeckName($character->ControllerId);            
            $deckCount = $deck->countCardsInLocation($deckName);
            
            if ($deckCount < $ownerDeckCount)
            {
                $targets[] = $character;
            }
        }

        return $targets;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner))
        {
            return false;
        }

        $targets = $this->getTargetsForAction($playerId, $theah);
        return count($targets) > 0;        
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01097", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01097)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args["performerId"] = $performerId;

            $characters = $this->getTargetsForAction($game->getActivePlayerId(), $game->theah);
            $args["ids"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01097)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            if ($target->ControllerId == $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot manipulate your own character"));
            }

            if ($target->Location != $owner->Location)
            {
                throw new \BgaUserException($game->translate("Character is not at the same location as the Performer"));
            }

            $deck = $game->theah->game->getGameDeckObject();

            $owner = $this->getOwningCharacter($game->theah);
            $ownerDeckName = $game->theah->game->getPlayerFactionDeckName($owner->ControllerId);
            $ownerDeckCount = $deck->countCardsInLocation($ownerDeckName);

            $deckName = $game->theah->game->getPlayerFactionDeckName($target->ControllerId);
            $deckCount = $deck->countCardsInLocation($deckName);

            if ($deckCount >= $ownerDeckCount)
            {
                throw new \BgaUserException($game->translate("Target character's owner has more cards in their deck than the Performer's owner"));
            }

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $target->Id, $owner->Id);
            $game->theah->queueEvent($engageEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);
            
            $game->gamestate->nextState("characterChosen");
        }
    }
}