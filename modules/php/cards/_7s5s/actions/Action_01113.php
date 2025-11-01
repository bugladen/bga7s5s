<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01113 extends RiskCityAction
{

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Take Control of Attachment in Opponent's Discard Pile");
        $this->RequiresPerformerSelected = true;
    }

    private function attachmentsAvailableFromOpponentDiscardPile(int $opponentId, Character $performer, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $handWealth = $theah->game->handWealthCount($owner->ControllerId);

        $discardPileName = $theah->game->getPlayerDiscardDeckName($opponentId);
        $cards = $theah->getCardObjectsAtLocation($discardPileName);
        $cards = array_filter($cards, fn($card) => $card instanceof Attachment);
        $availableAttachments = [];
        foreach ($cards as $card)
        {
            [$discount, $explanations] = $theah->getEquipDiscount($performer, $card);
            $cost = $card->WealthCost - $discount;
            if ($handWealth >= $cost)
            {
                [$hasRestrictions, $restrictionExplanation] = $theah->game->hasEquipRestrictions($performer, $card);
                if ($hasRestrictions)
                {
                    continue;
                }

                $availableAttachments[] = $card;
            }
        }
        return $availableAttachments;

    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $players = $theah->game->loadPlayersBasicInfos();
        $owner = $this->getOwningCard($theah);
        $performers = $theah->getCharactersInCityByPlayerId($owner->ControllerId);

        foreach ($players as $opponentId => $opponent)
        {
            if ($opponentId == $playerId)
            {
                continue;
            }

            foreach ($performers as $performer)
            {
                $availableAttachments = $this->attachmentsAvailableFromOpponentDiscardPile($opponentId, $performer, $theah);
                if (count($availableAttachments) > 0)
                {
                    return true;
                }
            }           

        }
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01113", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113)
        {
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            foreach ($players as $playerId => $player)
            {
                if ($playerId == $game->getActivePlayerId())
                {
                    continue;
                }

                $availableAttachments = $this->attachmentsAvailableFromOpponentDiscardPile($playerId, $performer, $game->theah);
                if (count($availableAttachments) > 0)
                {
                    $opponents[] = ['id' => $playerId, 'name' => $player['player_name']];
                }
            }

            $args['opponents'] = $opponents;            
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performerId;

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $args['opponentName'] = $game->getPlayerNameById($opponentId);

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $availableAttachments = $this->attachmentsAvailableFromOpponentDiscardPile($opponentId, $performer, $game->theah);

            $args['attachments'] = array_map(fn($attachment) => $attachment->getPropertyArray($game), $availableAttachments);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113_3)
        {
            $attachmentId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->getCardObjectFromDb($attachmentId);
            $args["cardId"] = $attachmentId;
            $args["cost"] = $game->globals->get(Game::CHOSEN_CARD_COST);
            $args["discount"] = $game->globals->get(Game::DISCOUNT);
            $args["card"] = $attachment->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113)
        {
            $players = $game->loadPlayersBasicInfos();
            if ( ! isset($players[$id]))
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $availableAttachments = $this->attachmentsAvailableFromOpponentDiscardPile($id, $performer, $game->theah);
            if (count($availableAttachments) == 0)
            {
                throw new \BgaUserException($game->translate("No attachments available from this opponent's discard pile"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113_2)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);

            $owner = $this->getOwningCard($game->theah);

            if ($attachment->ControllerId != $opponentId)
            {
                throw new \BgaUserException($game->translate("Card is not controlled by the Opponent."));
            }

            $discardPileName = $game->getPlayerDiscardDeckName($opponentId);
            if ($attachment->Location != $discardPileName)
            {
                throw new \BgaUserException($game->translate("Card is not in the Opponent's Discard Pile"));
            }

            [$discount, $explanations] = $game->theah->getEquipDiscount($performer, $attachment);
            $cost = $attachment->WealthCost - $discount;
            $handWealth = $game->handWealthCount($performer->ControllerId);
            if ($handWealth < $cost)
            {
                throw new \BgaUserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Attachment (%d with a discount of %d)."), $handWealth, $cost, $discount));
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($performer, $attachment);
            if ($hasRestrictions)
            {
                throw new \BgaUserException($restrictionExplanation);
            }   

            $attachment->ControllerId = $performer->ControllerId;
            $game->updateCardObjectInDb($attachment);
            $game->theah->addCardToWorld($attachment);

            $discardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($opponentId, $attachment->Id);
            $game->theah->queueEvent($discardEvent);

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);
            $game->globals->set(Game::CHOSEN_CARD_COST, $attachment->WealthCost);

            $discardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($opponentId, $attachment->Id);
            $game->theah->queueEvent($discardEvent);

            $addedToHandEvent = EventFactory::createCardAddedToHandEvent($performer->ControllerId, $attachment->Id);
            $game->theah->queueEvent($addedToHandEvent);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $attachment->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($event);
            
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01113_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("attachmentChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113_3)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $attachmentId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
    
            $discount = $game->globals->get(Game::DISCOUNT);
            $cost = $game->globals->get(Game::CHOSEN_CARD_COST);
            $explanations = $game->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
            $cost -= $discount;
            if ($cost < 0) $cost = 0;
    
            //Total up the wealth of the cards to see if player paid correctly
            $totalWealth = 0;
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card == null)
                    throw new \BgaUserException(sprintf($game->translate("Card #%d not found."), $cardId));
    
                //If $card has wealth in its traits, add it to the total wealth
                $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
            }
            if ($totalWealth != $cost) {
                throw new \BgaUserException(sprintf($game->translate("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
            }
    
            //Move the cards used to pay to the player's discard pile
            foreach ($ids as $cardId) 
            {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
                $game->theah->queueEvent($event);
            }
    
            $musterEvent = EventFactory::createAttachmentEquippedEvent($performer->ControllerId, $performer->Id, $attachment->Id, $discount, $cost, $asAction = false, $explanations);
            $game->theah->queueEvent($musterEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);
    
            $game->gamestate->nextState();

        }
    }
}

    