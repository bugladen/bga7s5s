<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02013 extends CharacterAction
{
    public ?int $TargetedCharacterId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Discard a Card. Issue a Challenge.");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) == 0)
        {
            return false;
        }

        $qualifyingCards = array_filter($hand, fn($card) => $card->hasTrait("Relic") || $card->hasTrait("Faith"));

        return count($qualifyingCards) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02013", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventChallengeIssued && $this->TargetedCharacterId == $event->defenderId)
        {
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($this->TargetedCharacterId);
            $character->removeTrait($event->theah->game, "Sorcerer");
            $this->TargetedCharacterId = null;
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02013_2)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $owner->Id;

            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($owner->ControllerId));
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02013)
        {
            $owner = $this->getOwningCharacter($game->theah);

            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            $hand = array_filter($hand, fn($card) => $card->Id == $id);
            if (count($hand) == 0)
            {
                throw new UserException($game->translate("Card not found in hand"));
            }

            if (! $card->hasTrait("Relic") && ! $card->hasTrait("Faith"))
            {
                throw new UserException($game->translate("Card is not a Relic or Faith card"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $card->Id);

            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02013_2)
        {
            $character = $game->theah->getCardById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $wilhelm = $this->getOwningCard($game->theah);

            if ($character->Location != $wilhelm->Location)
            {
                throw new UserException($game->translate("Character is not at the same location as the Performer"));
            }

            if ($character->ControllerId == $wilhelm->ControllerId)
            {
                throw new UserException($game->translate("You cannot challenge yourself"));
            }

            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::WILHELM_DUNST_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $discardedCardId = $game->globals->get(Game::CHOSEN_CARD);
            $discardedCardEvent = EventFactory::createCardDiscardedFromHandEvent($wilhelm->ControllerId, $discardedCardId, $wilhelm->Id);
            $game->theah->queueEvent($discardedCardEvent);

            $character->addTrait($game, "Sorcerer");
            $this->TargetedCharacterId = $character->Id;
            $wilhelm->IsUpdated = true;

            $transitionEvent = EventFactory::createTransitionEvent($wilhelm->ControllerId, $wilhelm->Id, "02013_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $this->announceAction($game);
            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            //createActionResolvedEvent not needed because the challenge will issue it

            $game->gamestate->nextState();

        }
    }
}