<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01180 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Kaj Kousei: Equip Artifact from City Deck";
        $this->ShortName = "Equip Artifact from City Deck";
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180)
        {
            $deck = $game->getGameDeckObject();
            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);

            $game->notifyAllPlayers("message", clienttranslate('${player_name} chooses not to Equip any Artifacts.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            foreach ($deckCards as $deckCard) 
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);

                //Sink card to City Discard Pile
                $deck->insertCardOnExtremePosition($card->Id, Game::LOCATION_CITY_DECK, false);

                $game->notifyAllPlayers("message", clienttranslate('<strong>${card_name}</strong> has been sunk to the City Pile.'), [
                    'i18n' => ['card_name'],
                    "card_name" => $card->Name,
                ]);
        }

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180)
        {
            $deck = $game->getGameDeckObject();
            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);

            $found = false;
            foreach ($deckCards as $deckCard) 
            {
                if ($deckCard['id'] == $id)
                {
                    $found = true;
                    break;
                }
            }

            if (!$found)
            {
                throw new \BgaUserException($game->translate("Card $id is not in the top 4 cards."));
            }

            $chosenCard = $game->getCardObjectFromDb($id);

            if (! $chosenCard instanceof Attachment && ! in_array('Artifact', $chosenCard->Traits))
            {
                throw new \BgaUserException($game->translate("Card $id is not an Artifact."));
            }
        
            $game->globals->set(Game::CHOSEN_CARD, $id);

            $game->gamestate->nextState("cardChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_2)
        {
            $playerId = $game->getActivePlayerId();
            $owner = $this->getOwningCard($game->theah);
            $id = $ids[0];

            //Get characters in play at the same location
            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId);
            $ids = array_map(fn($character) => $character->Id, $characters);
            if ( ! in_array($id, $ids))
            {
                throw new \BgaUserException($game->translate("Character Id #$id is not in play at the same location as {$owner->Name}."));
            }

            $performer = $game->getCardObjectFromDb($id);
            if ($owner->ControllerId != $performer->ControllerId)
            {
                throw new \BgaUserException($game->translate("Character Id #$id is not controlled by the same player as {$owner->Name}."));
            }

            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);

            //Get the wealth cost of the chosen card
            if ($chosenAttachment instanceof Attachment)
                $cost = $chosenAttachment->WealthCost;

            //Get the hand of the player
            $wealth = $game->handWealthCount($playerId);

            //Get any discounts the player may have
            $discount = $game->theah->getEquipDiscount($performer, $chosenAttachment);

            if ($cost > $wealth - $discount)
            {
                throw new \BgaUserException($game->translate("You do not have enough Wealth to equip this card (with a discount of $discount)."));
            }        
        
            $game->globals->set(Game::CHOSEN_PERFORMER, $id);

            $game->gamestate->nextState("performerChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_3)
        {
            $attachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $attachment = $game->getCardObjectFromDb($attachmentId);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);

            if ($attachment instanceof Attachment)
                $cost = $attachment->WealthCost;
            
            $discount = $game->theah->getEquipDiscount($performer, $attachment);
            $cost -= $discount;
    
            //Total up the wealth of the cards to see if player paid correctly
            $totalWealth = 0;
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card == null)
                    throw new \BgaUserException($game->translate("Card $cardId not found."));
    
                    //If $card has wealth in its traits, add it to the total wealth
                $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
            }
            if ($totalWealth != $cost) {
                throw new \BgaUserException($game->translate("Cost of Attachment is {$cost}. You selected {$totalWealth} Wealth of cards."));
            }
    
            $playerId = $game->getActivePlayerId();
    
            $game->notifyAllPlayers('message', clienttranslate('${player_name} has chosen to Equip <strong>${card_name}</strong> from the top 4 cards of the City Deck.'), [
                'i18n' => ['card_name'],
                'player_name' => $game->getActivePlayerName(),
                'card_name' => $attachment->Name,
            ]);

            $deck = $game->getGameDeckObject();

            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);
            foreach ($deckCards as $deckCard) 
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                if ($deckCard['id'] == $attachment->Id) continue;

                //Sink card to City Discard Pile
                $deck->insertCardOnExtremePosition($card->Id, Game::LOCATION_CITY_DECK, false);

                $game->notifyAllPlayers("message", clienttranslate('<strong>${card_name}</strong> has been sunk to the City Discard Pile.'), [
                    'i18n' => ['card_name'],
                    "card_name" => $card->Name,
                ]);
            }

            //Equip the attachment
            $equipAttachmentEvent = EventFactory::createAttachmentEquippedEvent($playerId, $attachmentId, $performerId, $discount, $cost);
            $game->theah->eventCheck($equipAttachmentEvent);
            $game->theah->queueEvent($equipAttachmentEvent);
    
            //Move the cards used to pay to the player's discard pile
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($playerId, $card->Id);
                $game->theah->queueEvent($event);
            }
    
            $deck->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
    
            $game->gamestate->nextState("artifactEquipped");
        }
     }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180)
        {
            $deck = $game->getGameDeckObject();
            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);
            $cards = [];
            foreach ($deckCards as $deckCard) {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $args["cards"] = $cards;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_2)
        {
            $chosenCardId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenCard = $game->getCardObjectFromDb($chosenCardId);

            //Get characters in play at the same location
            $owningCard = $this->getOwningCard($game->theah);
            $characters = $game->theah->getCharactersAtLocation($owningCard->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $owningCard->ControllerId);
            $ids = array_map(fn($character) => $character->Id, $characters);

            $args['chosenCard'] = $chosenCard->getPropertyArray($game);
            $args['card_name'] = $chosenCard->Name;
            $args['ids'] = $ids;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_3)
        {
            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);
            $args['chosenAttachment'] = $chosenAttachment->getPropertyArray($game);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);
            $args['performerId'] = $performerId;

            $discount = $game->theah->getEquipDiscount($performer, $chosenAttachment);
            $args['discount'] = $discount;
        }

        return $args;
    }


    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $deck = $event->theah->game->getGameDeckObject();
            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);
            $names = [];
            $count = 0;
            foreach ($deckCards as $deckCard) {
                $card = $event->theah->game->getCardObjectFromDb($deckCard['id']);
                $names[] = $event->theah->game->translate($card->Name);
                if (in_array('Artifact', $card->Traits))
                    $count++;
            }

            $event->theah->game->notifyAllPlayers('message', clienttranslate('<strong>${card_name}</strong> found ${count} Artifacts in the top 4 cards of the City Deck. (${names})'), [
                'i18n' => ['card_name'],
                'card_name' => $this->Name,
                'count' => $count,
                'names' => implode(', ', $names)
            ]);

            $this->setUsed($event->theah, true);

            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition)
            {
                $transition->playerId = $event->playerId;
                $transition->transition = "01180";
                $transition->sourceId = $this->OwnerId;
            }
            $event->theah->queueEvent($transition);
        }
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);

        return $theah->cardInCity($owner);
    }
}