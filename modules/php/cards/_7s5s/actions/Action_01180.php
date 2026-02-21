<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01180 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip Artifact from City Deck");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {  
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $kaj = $this->getOwningCard($theah);

        if (! $kaj->isControlled())
        {
            return false;
        }

        return $theah->cardInCity($kaj);
    }
    
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $deckCards = $event->theah->game->getCardsOnTopOfCityDeck(4);
            $names = [];
            $count = 0;
            foreach ($deckCards as $deckCard) {
                $card = $event->theah->game->getCardObjectFromDb($deckCard['id']);
                $names[] = $card->getInjectCode();
                if ($card->hasTrait('Artifact'))
                    $count++;
            }

            $kaj = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${player_name} has used the [${action}] Action from ${owner_inject_code}'), [
                'i18n' => ['action'],
                'player_name' => $game->getPlayerNameById($kaj->ControllerId),
                'action' => $this->Name,
                'owner_inject_code' => $kaj->getInjectCode(),
            ]);

            $this->setUsed($event->theah, true);
            $event->theah->game->notify->all('message', clienttranslate('${action_inject_code}: ${count} Artifacts found in the top 4 cards of the City Deck. (${names})'), [
                'action_inject_code' => $kaj->getInjectCode(),
                'count' => $count,
                'names' => implode(', ', $names)
            ]);

            $this->resetPlayerPassCount($event->theah->game);

            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01180", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array 
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180 || $state == States::HIGH_DRAMA_PLAYER_TURN_01180_3)
        {
            $deckCards = $game->getCardsOnTopOfCityDeck(4);
            $cards = [];
            foreach ($deckCards as $deckCard) {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                $cards[] = $card->getPropertyArray($game);
            }

            $args["cards"] = $cards;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_4)
        {
            $chosenCardId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenCard = $game->getCardObjectFromDb($chosenCardId);

            //Get characters in play at the same location
            $owningCard = $this->getOwningCard($game->theah);
            $characters = $game->theah->getCharactersAtLocation($owningCard->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId == $owningCard->ControllerId));
            $ids = array_map(fn($character) => $character->Id, $characters);

            $args['chosenCard'] = $chosenCard->getPropertyArray($game);
            $args['card_name'] = $chosenCard->Name;
            $args['ids'] = $ids;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_5)
        {
            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);
            $args['chosenAttachment'] = $chosenAttachment->getPropertyArray($game);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);
            $args['performerId'] = $performerId;

            $discount = $game->globals->get(Game::DISCOUNT);
            $args['discount'] = $discount;
            if ($chosenAttachment instanceof IWealthCost)
                $args['cost'] = $chosenAttachment->getWealthCost();
        }

        return $args;
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_3)
        {
            $deckCards = $game->getCardsOnTopOfCityDeck(4);

            $game->notify->all("message", clienttranslate('${player_name} chooses not to Equip any Artifacts.'), [
                "player_name" => $game->getActivePlayerName(),
            ]);

            foreach ($deckCards as $deckCard) 
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);

                //Sink card to City Discard Pile
                $event = EventFactory::createCardAddedToCityDeckEvent($game->getActivePlayerId(), $card->Id, false);
                $game->theah->queueEvent($event);
            }

            $owner = $this->getOwningCard($game->theah);
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void  
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_3)
        {
            $deckCards = $game->getCardsOnTopOfCityDeck(4);

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
                throw new \BgaUserException(sprintf($game->translate("Card %s is not in the top 4 cards."), $id));
            }

            $chosenCard = $game->getCardObjectFromDb($id);

            if (! $chosenCard instanceof Attachment && ! $chosenCard->hasTrait('Artifact'))
            {
                throw new \BgaUserException(sprintf($game->translate("Card %s is not an Artifact."), $id));
            }
        
            $game->globals->set(Game::CHOSEN_CARD, $id);

            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_4)
        {
            $playerId = $game->getActivePlayerId();
            $owner = $this->getOwningCard($game->theah);

            //Get characters in play at the same location
            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_values(array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId));
            $ids = array_map(fn($character) => $character->Id, $characters);
            if ( ! in_array($id, $ids))
            {
                throw new \BgaUserException(sprintf($game->translate("Character Id #%s is not in play at the same location as %s."), $id, $owner->Name));
            }

            $performer = $game->getCardObjectFromDb($id);
            if ($owner->ControllerId != $performer->ControllerId)
            {
                throw new \BgaUserException(sprintf($game->translate("Character Id #%s is not controlled by the same player as %s."), $id, $owner->Name));
            }

            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);

            //Get the wealth cost of the chosen card
            if ($chosenAttachment instanceof Attachment)
                $cost = $chosenAttachment->WealthCost;

            //Get the hand of the player
            $wealth = $game->handWealthCount($playerId);

            //Get any discounts the player may have
            [$discount, $explanations] = $game->theah->getEquipDiscount($performer, $chosenAttachment);

            if ($cost > $wealth - $discount)
            {
                throw new \BgaUserException(sprintf($game->translate("You do not have enough Wealth to equip this card (with a discount of %s)."), $discount));
            }        
        
            $game->globals->set(Game::CHOSEN_PERFORMER, $id);

            $event = EventFactory::createEnteringPayStateEvent($performer->ControllerId, $chosenAttachment->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($event);

            $transition = EventFactory::createTransitionEvent($performer->ControllerId, $performer->Id, "01180_4", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("performerChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void  
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01180_5)
        {
            $attachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $attachment = $game->theah->getAttachmentById($attachmentId);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($attachment instanceof Attachment)
                $cost = $attachment->WealthCost;
            
            $discount = $game->globals->get(Game::DISCOUNT);
            $explanations = $game->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
            $cost -= $discount;
    
            //Total up the wealth of the cards to see if player paid correctly
            $totalWealth = 0;
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card == null)
                    throw new \BgaUserException(sprintf($game->translate("Card %d not found."), $cardId));
    
                //If $card has wealth in its traits, add it to the total wealth
                $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
            }
            if ($totalWealth != $cost) {
                throw new \BgaUserException(sprintf($game->translate("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
            }
    
            $playerId = $game->getActivePlayerId();
    
            // This announcement is used in lieu of $this->announceAction()
            $game->notify->all('message', clienttranslate('${player_name} has chosen to Equip ${card_inject_code} from the top 4 cards of the City Deck.'), [
                'player_name' => $game->getActivePlayerName(),
                'card_inject_code' => $attachment->getInjectCode(),
            ]);

            $deckCards = $game->getCardsOnTopOfCityDeck(4);
            foreach ($deckCards as $deckCard) 
            {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                if ($deckCard['id'] == $attachment->Id) continue;

                $event = EventFactory::createCardAddedToCityDeckEvent($playerId, $card->Id, false);
                $game->theah->queueEvent($event);
            }

            //Some attachments actually attach to different targets
            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $performerId);

            //Equip the attachment
            $equipAttachmentEvent = EventFactory::createAttachmentEquippedEvent($playerId, $actualTargetId, $attachmentId, $discount, $cost, $asAction = true, $explanations);
            $game->theah->eventCheck($equipAttachmentEvent);
            $game->theah->queueEvent($equipAttachmentEvent);
    
            //Move the cards used to pay to the player's discard pile
            $owner = $this->getOwningCard($game->theah);
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $owner->Id, $asPayment = true);
                $game->theah->queueEvent($event);
            }
    
            $deck = $game->getGameDeckObject();
            $deck->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
    
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("artifactEquipped");
        }
     }
}