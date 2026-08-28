<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01113 extends Maneuver implements IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Take Control of Attachment");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }
        
        $actor = $theah->getDuelRoundActor();
        if (! $actor->hasTrait("Pirate"))
        {
            return false;
        }

        $adversaryId = $theah->getDuelOpponentId($actor->Id);
        $adversary = $theah->getCharacterById($adversaryId);

        $availableAttachments = [];
        $handWealth = $theah->game->handWealthCount($actor->ControllerId);
        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment)
            {
                [$discount, $explanations] = $theah->getEquipDiscount($actor, $attachment);
                $cost = $attachment->WealthCost - $discount;
                if ($handWealth >= $cost)
                {
                    [$hasRestrictions, $restrictionExplanation] = $theah->game->hasEquipRestrictions($actor, $attachment);

                    if ($hasRestrictions || ! $attachment->canAttachTo($actor) || ! $attachment->canBeMoved())
                    {
                        continue;
                    }
        
                    $availableAttachments[] = $attachment;
                }
            }
        }

        $availableAttachments = array_merge($availableAttachments, $theah->attachmentsAvailableFromOpponentDiscardPile($adversary->ControllerId, $actor));
        return count($availableAttachments) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed since this maneuver does not have any internal state

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01113", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01113)
        {
            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);
            $handWealth = $game->handWealthCount($actor->ControllerId);  

            $availableAttachments = [];
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment)
                {
                    [$discount, $explanations] = $game->theah->getEquipDiscount($actor, $attachment);
                    $cost = $attachment->WealthCost - $discount;
                    if ($handWealth >= $cost)
                    {
                        [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($actor, $attachment);
                        if ($hasRestrictions || ! $attachment->canAttachTo($actor) || ! $attachment->canBeMoved())
                        {
                            continue;
                        }

                        $availableAttachments[] = ["id" => $attachment->Id, "name" => $attachment->Name, "location" => $game->translate("Attached")];
                    }
                }
            }

            $attachmentsFromDiscardPile = $game->theah->attachmentsAvailableFromOpponentDiscardPile($adversary->ControllerId, $actor);
            foreach ($attachmentsFromDiscardPile as $attachment)
            {
                $availableAttachments[] = ["id" => $attachment->Id, "name" => $attachment->Name, "location" => $game->translate("Discard Pile"), "cost" => $attachment->WealthCost, "discount" => 0, "explanations" => []];
            }

            $args["attachments"] = $availableAttachments;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_01113_2)
        {
            $cardId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
            $card = $game->getCardObjectFromDb($cardId);
            $args["attachmentId"] = $cardId;
            $args["cost"] = $game->globals->get(Game::CHOSEN_CARD_COST);
            $args["discount"] = $game->globals->get(Game::DISCOUNT);
            $args["card"] = $card->getPropertyArray($game);
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01113)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment == null)
            {
                throw new UserException($game->translate("Card not found"));
            }

            $actor = $game->theah->getDuelRoundActor();

            if (! $actor->hasTrait("Pirate"))
            {
                throw new UserException($game->translate("Actor is not a Pirate"));
            }

            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);
            $owner = $this->getOwningCard($game->theah);

            if ($attachment->ControllerId != $adversary->ControllerId)
            {
                throw new UserException($game->translate("Card is not controlled by the Adversary."));
            }

            $discardPileName = $game->getPlayerDiscardDeckName($adversary->ControllerId);
            if ($attachment->Location != $discardPileName && $attachment->Location != $adversary->Location)
            {
                throw new UserException($game->translate("Card is not in the Adversary's Discard Pile or Attached to Adversary"));
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($actor, $attachment);
            if ($hasRestrictions)
            {
                throw new UserException($restrictionExplanation);
            }

            if (! $attachment->canAttachTo($actor))
            {
                throw new UserException($game->translate("Attachment cannot be attached to the Actor."));
            }

            if ($attachment->Location == $adversary->Location && ! $attachment->canBeMoved())
            {
                throw new UserException(sprintf($game->translate('%s cannot be moved.'), $attachment->Name));
            }

            [$discount, $explanations] = $game->theah->getEquipDiscount($actor, $attachment);
            $cost = $attachment->WealthCost - $discount;
            $handWealth = $game->handWealthCount($actor->ControllerId);
            if ($handWealth < $cost)
            {
                throw new UserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Attachment (%d with a discount of %d)."), $handWealth, $cost, $discount));
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($actor, $attachment);
            if ($hasRestrictions)
            {
                throw new UserException($restrictionExplanation);
            }

            $attachment->ControllerId = $actor->ControllerId;
            $game->updateCardObjectInDb($attachment);
            $game->theah->addCardToWorld($attachment);

            $game->globals->set(Game::CHOSEN_PERFORMER, $actor->Id);

            if ($attachment->Location == $adversary->Location)
            {
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent($adversary->ControllerId, $adversary->Id, $attachment->Id);
                $game->theah->queueEvent($unequipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($adversary->ControllerId, $attachment->Id, $attachment->Location, $owner->Id);
                $game->theah->queueEvent($discardEvent);
            }

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            $game->globals->set(Game::CHOSEN_CARD_COST, $attachment->WealthCost);
    
            $discardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($adversary->ControllerId, $attachment->Id);
            $game->theah->queueEvent($discardEvent);

            $addedToHandEvent = EventFactory::createCardAddedToHandEvent($actor->ControllerId, $attachment->Id);
            $game->theah->queueEvent($addedToHandEvent);

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $attachment->Id, Game::PAY_STATE_EQUIP_ATTACHMENT);
            $game->theah->queueEvent($event);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01113_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState();
        }

    }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromManeuverWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01113_2)
        {
            $actor = $game->theah->getDuelRoundActor();
            $attachmentId = $game->globals->get(Game::CHOSEN_ATTACHMENT);
            $attachment = $game->theah->getAttachmentById($attachmentId);
    
            //Sanity checks
            if ($attachment == null || $attachment->Location != Game::LOCATION_HAND || $attachment->ControllerId != $actor->ControllerId) 
            {
                throw new \BgaUserException($game->translate("Attachment is not in Player's Hand."));
            }
    
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
            $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $actor->Id);

            $owner = $this->getOwningCard($game->theah);
            $musterEvent = EventFactory::createAttachmentEquippedEvent($actor->ControllerId, $actualTargetId, $attachment->Id, $discount, $cost, $asAction = false, $explanations, false, $owner->Id, $this->Id);
            $game->theah->queueEvent($musterEvent);
    
            $game->gamestate->nextState();
        }
    }

}