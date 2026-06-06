<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01185 extends EventCityAction
{
    public function __construct()
    {
        parent::__construct();
        
        $this->Name = clienttranslate("Add a Renown");
    }    

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        //If player does not have at least 2 cards in hand, do not show as available
        $deck = $theah->game->getGameDeckObject();
        $cards = $deck->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        if (count($cards) < 2)
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01185", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01185)
        {
            $riskyUndertaking = $this->getOwningCard($game->theah);
            $args['id'] = $riskyUndertaking->Id;
        }

        return $args;
    }


    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01185)
        {
            if (count($ids) != 2)
            {
                throw new UserException(sprintf($game->translate("Invalid number of cards selected for action: %d"), count($ids)));
            }

            $riskyUndertaking = $this->getOwningCard($game->theah);

            $playerId = $game->getActivePlayerId();
            $playerName = $game->getPlayerNameById($playerId);


            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card == null)
                {
                    throw new UserException(sprintf($game->translate("Card not found: %d"), $cardId));
                }
                if ($card->OwnerId != $playerId)
                {
                    throw new UserException(sprintf($game->translate("Card is not owned by the active player: %d"), $cardId));
                }
                if ($card->Location != Game::LOCATION_HAND)
                {
                    throw new UserException(sprintf($game->translate("Card is not in the hand: %d"), $cardId));
                }
            }

            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id, $riskyUndertaking->Id, $asPayment = true);
                $game->theah->queueEvent($event);
            }

            //Add a reknown to the location
            $location = $riskyUndertaking->Location;            
            $event = EventFactory::createRenownAddedToLocationEvent($playerId, $location, 1, $playerName);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            //Discard this card
            $event = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $riskyUndertaking->Id, $location, $riskyUndertaking->Id, $asEffect = true);
            $game->theah->queueEvent($event);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardsDiscarded");
        }
    }

}