<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01095b extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Draw Card, or other players discard a card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $patricia = $this->getOwningCharacter($theah);
        if ($patricia->Location != Game::LOCATION_CITY_DOCKS)
        {
            return false;
        }

        if ($patricia->Engaged)
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
            $game = $event->theah->game;
            $patricia = $this->getOwningCharacter($event->theah);

            $engageEvent = EventFactory::createCardEngagedEvent($patricia->ControllerId, $patricia->Id, $patricia->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            $forcedNotFirstPlayer = $game->globals->get(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, false);
            $isfirstPlayer = $game->globals->get(Game::FIRST_PLAYER) == $patricia->ControllerId && ! $forcedNotFirstPlayer;

            if($isfirstPlayer)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($patricia->ControllerId, $patricia->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }
            else
            {
                $game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, $patricia->ControllerId);
                $challengeEvent = EventFactory::createTransitionEvent($patricia->ControllerId, $patricia->Id, "01095", $this->Id);
                $event->theah->queueEvent($challengeEvent);
            }

            $this->announceAction($game);
            $this->resetPlayerPassCount($game);
            $this->setUsed($event->theah, true);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($patricia->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01095)
        {
            $playerId = $game->getCurrentPlayerId();

            $card = $game->getCardObjectFromDb($id);

            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
            $hand = array_filter($hand, fn($card) => $card->Id == $id);
            if (count($hand) == 0)
            {
                throw new \BgaUserException($game->translate("Card is not in your hand"));
            }

            $owner = $this->getOwningCharacter($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id, $owner->Id, false, false, true);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->setPlayerNonMultiactive($playerId, 'multipleOk');
        }

    }

}