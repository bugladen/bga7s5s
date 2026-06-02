<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02007 extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Remove Renown, Discard a City Card");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_values(array_filter($characters, fn($character) => $character->hasTrait("Red Hand")));

        foreach ($characters as $character)
        {
            $location = $theah->getCityLocation($character->Location);
            if ($location->Renown > 0)
            {
                return true;
            }

            $cards = $theah->getCardObjectsAtLocation($character->Location);
            $cards = array_values(array_filter($cards, fn($card) => ! $card->isControlled()));
            if (count($cards) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($character) => $character->hasTrait("Red Hand")));
        $performers = array_values(array_filter($performers, function($character) use ($theah) {
            $location = $theah->getCityLocation($character->Location);
            if ($location->Renown > 0)
            {
                return true;
            }

            $cards = $theah->getCardObjectsAtLocation($character->Location);
            $cards = array_values(array_filter($cards, fn($card) => ! $card->isControlled()));
            return count($cards) > 0;
        }));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundEvent);

            $renownRemovedEvent = EventFactory::createRenownRemovedFromLocationEvent($performer->ControllerId, $performer->Location, 1, $owner->getInjectCode());
            $event->theah->queueEvent($renownRemovedEvent);

            $cards = $game->theah->getCardObjectsAtLocation($performer->Location);
            $cards = array_values(array_filter($cards, fn($card) => ! $card->isControlled()));
            if (count($cards) > 0)
            {
                $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $owner->Id, "02007", $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
            else
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
                $event->theah->queueEvent($actionResolvedEvent);
            }
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02007)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $cards = $game->theah->getCardObjectsAtLocation($performer->Location);
            $cards = array_values(array_filter($cards, fn($card) => ! $card->isControlled()));
            $args["ids"] = array_map(fn($card) => $card->Id, $cards);
        }

        return $args;
    }    

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02007)
        {
            $owner = $this->getOwningCard($game->theah);
            $card = $game->getCardObjectFromDb($id);
            if ($card == null)
            {
                throw new UserException(sprintf($game->translate("Card not found: %d"), $id));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($card->Location != $performer->Location)
            {
                throw new UserException(sprintf($game->translate("Card is not in the performer's location: %d"), $id));
            }

            if ($card->isControlled())
            {
                throw new UserException(sprintf($game->translate("Card is not available: %d"), $id));
            }

            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($performer->ControllerId, $card->Id, $performer->Location, $owner->Id, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}