<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;

trait ActionsTrait
{
    public function actPickDeck(string $deck_type, string $deck_id): void
    {
        $playerId = $this->getCurrentPlayerId();

        $sql = "UPDATE player SET deck_source = '$deck_type', deck_id = '$deck_id'  WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked'); // deactivate player; if none left, transition to 'deckPicked' state
    }

    public function actDayPlanned(int $scheme, int $character): void
    {
        $playerId = $this->getCurrentPlayerId();
        $sql = "UPDATE player SET selected_scheme_id = '$scheme', selected_character_id = '$character'  WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        //Move the cards to a purgatory state while waiting for the other players to finish their day planning.
        //This is necessary to prevent the card from being shown back in the player's approach deck if they F5.
        $this->cards->moveCard($scheme, Game::LOCATION_PURGATORY);
        $this->cards->moveCard($character, Game::LOCATION_PURGATORY);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned'); // deactivate player; if none left, transition to 'deckPicked' state
    }

    public function actCityLocationsForReknownSelected(string $locations)
    {
        $this->theah->buildCity();

        $locations = json_decode($locations, true);
        foreach ($locations as $location) {
            $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
            if ($event instanceof EventReknownAddedToLocation) {
                $event->location = $location;
                $event->amount = 1;
            }
            $this->theah->queueEvent($event);
        }

        // Go back and finish running the Scheme events
        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01044(int $id)
    {
        $playerId = $this->getActivePlayerId();

        //Move card in DB
        $this->cards->moveCard($id, Game::LOCATION_HAND, $playerId);
        $card = $this->getCardObjectFromDb($id);

        $event = $this->theah->createEvent(Events::CardRemovedFromPlayerDiscardPile);
        if ($event instanceof EventCardRemovedFromPlayerDiscardPile) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $event = $this->theah->createEvent(Events::CardAddedToHand);
        if ($event instanceof EventCardAddedToHand) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01045(int $id)
    {
        $playerId = $this->getActivePlayerId();
        $playerName = $this->getActivePlayerName();

        //Move card to top of City Deck
        $this->cards->insertCardOnExtremePosition($id, Game::LOCATION_CITY_DECK, true);
        $card = $this->getCardObjectFromDb($id);

        // Notify players of selected scheme
        $this->notifyAllPlayers("message_0145", clienttranslate('${player_name} chose ${card_name} to move from the City Discard Pile to the top of the City Deck.'), [
            "player_name" => $playerName,
            "card_name" => "<span style='font-weight:bold'>{$card->Name}</span>",
            "player_id" => $playerId,
            "card" => $card->getPropertyArray(),
        ]);

        $event = $this->theah->createEvent(Events::CardRemovedFromCityDiscardPile);
        if ($event instanceof EventCardRemovedFromCityDiscardPile) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $event = $this->theah->createEvent(Events::CardAddedToCityDeck);
        if ($event instanceof EventCardAddedToCityDeck) {
            $event->card = $card;
            $event->playerId = $playerId;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlanningPhase_01150(string $locations)
    {
        $this->theah->buildCity();

        $playerName = $this->getActivePlayerName();

        $locations = json_decode($locations, true);
        $location = array_shift($locations);
        $event = $this->theah->createEvent(Events::ReknownRemovedFromLocation);
        if ($event instanceof EventReknownRemovedFromLocation) {
            $event->location = $location;
            $event->amount = 1;
            $event->source = $playerName;
        }
        $this->theah->queueEvent($event);

        $event = $this->theah->createEvent(Events::ReknownAddedToLocation);
        if ($event instanceof EventReknownAddedToLocation) {
            $event->location = Game::LOCATION_CITY_FORUM;
            $event->amount = 1;
            $event->source = $playerName;
        }
        $this->theah->queueEvent($event);

        $this->gamestate->nextState("");
    }

    public function actPlayCard(int $card_id): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // check input values
        // $args = $this->argPlayerTurn();
        // $playableCardsIds = $args['playableCardsIds'];
        // if (!in_array($card_id, $playableCardsIds)) {
        //     throw new \BgaUserException('Invalid card choice');
        // }

        // Add your game logic to play a card here.
        // $card_name = $this->card_types[$card_id]['card_name'];

        // Notify all players about the card played.
        // $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
        //     "player_id" => $player_id,
        //     "player_name" => $this->getActivePlayerName(),
        //     "card_name" => $card_name,
        //     "card_id" => $card_id,
        //     "i18n" => ['card_name'],
        // ]);

        // at the end of the action, move to the next state
        // $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("playerPasses", clienttranslate('${player_name} passes.'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("");
    }
}