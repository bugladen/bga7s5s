<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01113 extends RiskCityAction implements IAbilityThatTargetsCards
{

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Take Control of Attachment in Opponent's Discard Pile");
        $this->RequiresPerformerSelected = true;
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

        // When this action triggers, the Risk card leaves the hand (plus any cards paying its WealthCost).
        // Subtract that wealth so we don't count it toward affording the attachment.
        if ($owner instanceof IWealthCost)
        {
            $selfWealth = $owner->hasTrait("Wealth") ? 2 : 1;
            $wealthAdjustment = -($selfWealth + $owner->getWealthCost());
        }

        foreach ($players as $opponentId => $opponent)
        {
            if ($opponentId == $playerId)
            {
                continue;
            }

            foreach ($performers as $performer)
            {
                if (! $performer->hasTrait("Pirate"))
                {
                    continue;
                }

                $availableAttachments = $theah->attachmentsAvailableFromOpponentDiscardPile($opponentId, $performer, $wealthAdjustment);
                if (count($availableAttachments) > 0)
                {
                    return true;
                }
            }

        }
        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait("Pirate")));
        return $performers;
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

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113)
        {
            $owner = $this->getOwningCard($game->theah);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
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

                $availableAttachments = $game->theah->attachmentsAvailableFromOpponentDiscardPile($playerId, $performer);
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
            $availableAttachments = $game->theah->attachmentsAvailableFromOpponentDiscardPile($opponentId, $performer);

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
                throw new UserException($game->translate("Invalid opponent"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if (! $performer->hasTrait("Pirate"))
            {
                throw new UserException($game->translate("Performer is not a Pirate"));
            }

            $availableAttachments = $game->theah->attachmentsAvailableFromOpponentDiscardPile($id, $performer);
            if (count($availableAttachments) == 0)
            {
                throw new UserException($game->translate("No attachments available from this opponent's discard pile"));
            }

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);
            $game->gamestate->nextState("playerChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01113_2)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);

            $owner = $this->getOwningCard($game->theah);

            if ($attachment->ControllerId != $opponentId)
            {
                throw new UserException($game->translate("Card is not controlled by the Opponent."));
            }

            $discardPileName = $game->getPlayerDiscardDeckName($opponentId);
            if ($attachment->Location != $discardPileName)
            {
                throw new UserException($game->translate("Card is not in the Opponent's Discard Pile"));
            }

            [$discount, $explanations] = $game->theah->getEquipDiscount($performer, $attachment);
            $cost = $attachment->WealthCost - $discount;
            $handWealth = $game->handWealthCount($performer->ControllerId);
            if ($handWealth < $cost)
            {
                throw new UserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Attachment (%d with a discount of %d)."), $handWealth, $cost, $discount));
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($performer, $attachment);
            if ($hasRestrictions)
            {
                throw new UserException($restrictionExplanation);
            }

            if (! $attachment->canAttachTo($performer))
            {
                throw new UserException($game->translate("Attachment cannot be attached to the Performer."));
            }

            $attachment->ControllerId = $performer->ControllerId;
            $game->updateCardObjectInDb($attachment);
            $game->theah->addCardToWorld($attachment);

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
            $hasWealthCard = false;
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card == null)
                    throw new UserException(sprintf($game->translate("Card #%d not found."), $cardId));

                //If $card has wealth in its traits, add it to the total wealth
                $isWealth = $card->hasTrait("Wealth");
                if ($isWealth) $hasWealthCard = true;
                $totalWealth += $isWealth ? 2 : 1;
            }
            if (!$game->isValidWealthPayment($totalWealth, $cost, $hasWealthCard)) {
                throw new UserException(sprintf($game->translate("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
            }
    
            //Move the cards used to pay to the player's discard pile
            foreach ($ids as $cardId) 
            {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $sourceId = 0, $asPayment = true);
                $game->theah->queueEvent($event);
            }
    
            //Some attachments actually attach to different targets
            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $performer->Id);

            $owner = $this->getOwningCard($game->theah);
            $musterEvent = EventFactory::createAttachmentEquippedEvent($performer->ControllerId, $actualTargetId, $attachment->Id, $discount, $cost, $asAction = false, $explanations, false, $owner->Id, $this->Id);
            $game->theah->queueEvent($musterEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);
    
            $game->gamestate->nextState();

        }
    }
}

    