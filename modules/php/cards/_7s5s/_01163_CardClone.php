<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;

class _01163_CardClone extends Card
{
    public int $ClonedCardId = 0;
    public int $ParentCardId = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskPhaseBegin)
        {
            $location = $event->theah->getCityLocation($this->Location);
            if ($location->Controller == $this->ControllerId)
            {
                $game = $event->theah->game;
                $clonedCard = $event->theah->getCardById($this->ClonedCardId);
                $parentCard = $event->theah->getCardById($this->ParentCardId);
                $game->notify->all("message", clienttranslate('${parent_card_inject_code}: ${player_name} controls ${location}.  Hidden card will be added to their hand.'), [
                    'i18n' => ['location'],
                    "parent_card_inject_code" => $parentCard->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($this->ControllerId),
                    "location" => $location->Name,
                ]);

                //This clone card removed from play
                $deck = $game->getGameDeckObject();
                $deck->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN, $this->ControllerId);
                $game->notify->all("cardRemovedFromPlay", "", [
                    "cardId" => $this->Id,
                    "toLocation" => Game::LOCATION_PERMANENTLY_HIDDEN,
                ]);

                //Add the hidden card to the controller's hand
                $cardAddedEvent = EventFactory::createCardAddedToHandEvent($this->ControllerId, $clonedCard->Id, true);
                $event->theah->queueEvent($cardAddedEvent);
            }
            else
            {
                $game = $event->theah->game;
                $clonedCard = $event->theah->getCardById($this->ClonedCardId);
                $parentCard = $event->theah->getCardById($this->ParentCardId);
                $game->notify->all("message", clienttranslate('${parent_card_inject_code}: ${player_name} does not control ${location}.  Hidden card will be discarded.'), [
                    'i18n' => ['location'],
                    "parent_card_inject_code" => $parentCard->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($this->ControllerId),
                    "location" => $location->Name,
                ]);

                //The clone card is removed from play
                $deck = $game->getGameDeckObject();
                $deck->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN, $this->ControllerId);
                $game->notify->all("cardRemovedFromPlay", "", [
                    "cardId" => $this->Id,
                    "toLocation" => Game::LOCATION_PERMANENTLY_HIDDEN,
                ]);

                //Discard the cloned card to the player's discard pile
                $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
                $deck->moveCard($clonedCard->Id, $game->getPlayerDiscardDeckName($clonedCard->ControllerId));
                $game->notify->all("cardAddedToPlayerDiscardPile", clienttranslate('${card_inject_code} added to ${player_name} discard pile.'), [
                    "card_inject_code" => $clonedCard->getInjectCode(),
                    "playerId" => $clonedCard->ControllerId,
                    "player_name" => $game->getPlayerNameById($clonedCard->ControllerId),
                    "card" => $clonedCard->getPropertyArray($game),                
                ]);
           }
        }
    }
}