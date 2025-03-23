<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01180;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01180 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = "Kaj Kousei";
        $this->Image = "img/cards/7s5s/180.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 180;

        $this->Title = 'The Thorn';

        $this->Resolve = 4;
        $this->Combat = 1;
        $this->Finesse = 2;
        $this->Influence = 2;

        $this->resetModifiedCharacterStats();

        $this->WealthCost = 4;        
        $this->CityCardNumber = 4;
        $this->Negotiable = true;

        $this->Traits = [
            'Mercenary',
            'Academic',
            "Explorer's Society",
            'Numa'
        ];

        $this->Actions = [
            new Action_01180()
        ];
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment);

        //While equipping an Artifact to any of the controllers characters, Kaj Kousei gives a discount of 1
        if ($performer->ControllerId == $this->ControllerId && in_array('Artifact', $attachment->Traits))
        {
            $discount += 1;
        }
        return $discount;
    }

    public function argsFromCard(Game $game, int $state): array
    {
        $args = parent::argsFromCard($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180)
        {
            $deck = $game->getGameDeckObject();
            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);
            $cards = [];
            foreach ($deckCards as $deckCard) {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray();
            }

            $args["cards"] = $cards;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_2)
        {
            $chosenCardId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenCard = $game->getCardObjectFromDb($chosenCardId);

            //Get characters in play at the same location
            $characters = $game->theah->getCharactersAtLocation($this->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $this->ControllerId);
            $ids = array_map(fn($character) => $character->Id, $characters);

            $args['chosenCard'] = $chosenCard->getPropertyArray();
            $args['card_name'] = $chosenCard->Name;
            $args['ids'] = $ids;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_3)
        {
            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);
            $args['chosenAttachment'] = $chosenAttachment->getPropertyArray();

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);
            $args['performerId'] = $performerId;

            $discount = $game->theah->getEquipDiscount($performer, $chosenAttachment);
            $args['discount'] = $discount;
        }

        return $args;
    }

    public function actFromCardPass(Game $game, int $state): void
    {
        parent::actFromCardPass($game, $state);

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

                $game->notifyAllPlayers("message", clienttranslate('${card_name} has been sunk to the City Pile.'), [
                    "card_name" => "<strong>{$card->Name}</strong>",
                ]);
        }

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromCardWithId(Game $game, int $state, int $id): void
    {
        parent::actFromCardWithId($game, $state, $id);

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
                throw new \BgaUserException("Card $id is not in the top 4 cards.");
            }

            $chosenCard = $game->getCardObjectFromDb($id);

            if (! $chosenCard instanceof Attachment && ! in_array('Artifact', $chosenCard->Traits))
            {
                throw new \BgaUserException("Card $id is not an Artifact.");
            }
        
            $game->globals->set(Game::CHOSEN_CARD, $id);

            $game->gamestate->nextState("cardChosen");
        }
    }

    public function actFromCardWithIds(Game $game, int $state, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_2)
        {
            $playerId = $game->getActivePlayerId();
            $id = $ids[0];

            //Get characters in play at the same location
            $characters = $game->theah->getCharactersAtLocation($this->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $this->ControllerId);
            $ids = array_map(fn($character) => $character->Id, $characters);
            if ( ! in_array($id, $ids))
            {
                throw new \BgaUserException("Character Id #$id is not in play at the same location as {$this->Name}.");
            }

            $performer = $game->getCardObjectFromDb($id);

            if ($this->ControllerId != $performer->ControllerId)
            {
                throw new \BgaUserException("Character Id #$id is not controlled by the same player as {$this->Name}.");
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
                throw new \BgaUserException("You do not have enough Wealth to equip this card (with a discount of $discount).");
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
                    throw new \BgaUserException("Card $cardId not found.");
    
                    //If $card has wealth in its traits, add it to the total wealth
                $totalWealth += in_array("Wealth", $card->Traits) ? 2 : 1;
            }
            if ($totalWealth != $cost) {
                throw new \BgaUserException("Cost of Attachment is {$cost}. You selected {$totalWealth} Wealth of cards.");
            }
    
            $playerId = $game->getActivePlayerId();
    
            $game->notifyAllPlayers('message', clienttranslate('${player_name} has chosen to Equip ${card_name} from the top 4 cards of the City Deck.'), [
                'player_name' => $game->getActivePlayerName(),
                'card_name' => "<strong>{$attachment->Name}</strong>",
            ]);

            $deck = $game->getGameDeckObject();

            $deckCards = $deck->getCardsOnTop(4, Game::LOCATION_CITY_DECK);
            foreach ($deckCards as $deckCard) 
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                if ($deckCard['id'] == $attachment->Id) continue;

                //Sink card to City Discard Pile
                $deck->insertCardOnExtremePosition($card->Id, Game::LOCATION_CITY_DECK, false);

                $game->notifyAllPlayers("message", clienttranslate('${card_name} has been sunk to the City Discard Pile.'), [
                    "card_name" => "<strong>{$card->Name}</strong>",
                ]);
            }

            //Equip the attachment
            $equipAttachmentEvent = $game->theah->createEvent(Events::AttachmentEquipped);
            if ($equipAttachmentEvent instanceof EventAttachmentEquipped) {
                $equipAttachmentEvent->attachmentId = $attachment->Id;
                $equipAttachmentEvent->performerId = $performer->Id;
                $equipAttachmentEvent->playerId = $playerId;
                $equipAttachmentEvent->discount = $discount;
                $equipAttachmentEvent->cost = $cost;
            }
            $game->theah->eventCheck($equipAttachmentEvent);
    
            //Move the cards used to pay to the player's discard pile
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                $deck->moveCard($cardId, $game->getPlayerDiscardDeckName($playerId));
    
                $event = $game->theah->createEvent(Events::CardDiscardedFromHand);
                if ($event instanceof EventCardDiscardedFromHand) {
                    $event->playerId = $playerId;
                    $event->card = $card;
                }
                //No check needed
                $game->theah->queueEvent($event);
            }
    
            $deck->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
            $game->theah->queueEvent($equipAttachmentEvent);
    
            $game->gamestate->nextState("artifactEquipped");
        }
    }
}