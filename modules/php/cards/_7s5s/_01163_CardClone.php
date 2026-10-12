<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;

class _01163_CardClone extends Card
{
    public int $ClonedCardId = 0;
    public int $ParentCardId = 0;

    // WHY: stDuskPhaseCleanup zeroes location Controllers before panache discard and
    // EventDuskEndOfDay. Card text is "end of the day" — hand/discard must run AFTER
    // dusk discard-to-panache, so control is snapshotted at DuskPhaseBegin (controllers
    // still valid) and resolved on EventDuskEndOfDay. null = not yet snapshotted.
    public ?bool $ControlledLocationAtDuskBegin = null;

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
            $this->ControlledLocationAtDuskBegin = ($location->Controller == $this->ControllerId);
            $event->theah->game->updateCardObjectInDb($this);
            return;
        }

        if ($event instanceof EventDuskEndOfDay && $this->ControlledLocationAtDuskBegin !== null)
        {
            $game = $event->theah->game;
            $parentCard = $event->theah->getCardById($this->ParentCardId);
            $locationName = $this->Location;

            if ($this->ControlledLocationAtDuskBegin)
            {
                $clonedCard = $event->theah->getCardById($this->ClonedCardId);
                $game->notify->all("message", clienttranslate('${parent_card_inject_code}: ${player_name} controls ${location}.  Hidden card will be added to their hand.'), [
                    'i18n' => ['location'],
                    "parent_card_inject_code" => $parentCard->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($this->ControllerId),
                    "location" => $locationName,
                ]);

                //This clone card removed from play
                $game->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN, $this->ControllerId, $this);
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
                $game->notify->all("message", clienttranslate('${parent_card_inject_code}: ${player_name} does not control ${location}.  Hidden card will be discarded.'), [
                    'i18n' => ['location'],
                    "parent_card_inject_code" => $parentCard->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($this->ControllerId),
                    "location" => $locationName,
                ]);

                //The clone card is removed from play
                $game->moveCard($this->Id, Game::LOCATION_PERMANENTLY_HIDDEN, $this->ControllerId, $this);
                $game->notify->all("cardRemovedFromPlay", "", [
                    "cardId" => $this->Id,
                    "toLocation" => Game::LOCATION_PERMANENTLY_HIDDEN,
                ]);

                //Discard the cloned card to the player's discard pile
                $clonedCard = $game->getCardObjectFromDb($this->ClonedCardId);
                $discardPileName = $game->getPlayerDiscardDeckName($clonedCard->ControllerId);
                $game->moveCard($clonedCard->Id, $discardPileName, 0, $clonedCard);

                $game->notify->all("cardAddedToPlayerDiscardPile", clienttranslate('${card_inject_code} added to ${player_name} discard pile.'), [
                    "card_inject_code" => $clonedCard->getInjectCode(),
                    "playerId" => $clonedCard->ControllerId,
                    "player_name" => $game->getPlayerNameById($clonedCard->ControllerId),
                    "card" => $clonedCard->getPropertyArray($game),
                ]);
            }

            $this->ControlledLocationAtDuskBegin = null;
            $game->updateCardObjectInDb($this);
        }
    }
}
