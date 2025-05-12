<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

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
        
        $this->Name = "Risky Undertaking: Add a Reknown";
        $this->ShortName = "Add a Reknown";
    }    

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah))
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
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01185");
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
                throw new \BgaUserException($game->translate("Invalid number of cards selected for action: " . count($ids)));
            }

            $playerName = $game->getActivePlayerName();

            $game->notifyAllPlayers("message", clienttranslate('${player_name} has activated Risky Undertaking'), [
                'player_name' => $playerName,
            ]);

            //Move the cards used to pay to the player's discard pile
            $deck = $game->getGameDeckObject();
            $playerId = $game->getActivePlayerId();
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
                $game->theah->queueEvent($event);
            }

            //Add a reknown to the location
            $riskyUndertaking = $this->getOwningCard($game->theah);
            $location = $riskyUndertaking->Location;            
            $event = EventFactory::createReknownAddedToLocationEvent($playerId, $location, 1, $playerName);
            $game->theah->queueEvent($event);

            //Discard this card
            $event = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $riskyUndertaking->Id, $location);
            $game->theah->queueEvent($event);

            $game->gamestate->nextState("cardsDiscarded");
        }
    }

}