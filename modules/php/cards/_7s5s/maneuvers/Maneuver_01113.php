<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01113 extends Maneuver
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

                    if ($hasRestrictions)
                    {
                        continue;
                    }
        
                    $availableAttachments[] = $attachment;
                }
            }
        }

        $discardPileName = $theah->game->getPlayerDiscardDeckName($adversary->ControllerId);
        $cards = $theah->getCardObjectsAtLocation($discardPileName);
        $cards = array_filter($cards, fn($card) => $card instanceof Attachment);
        foreach ($cards as $card)
        {
            [$discount, $explanations] = $theah->getEquipDiscount($actor, $card);
            $cost = $card->WealthCost - $discount;
            if ($handWealth >= $cost)
            {
                $availableAttachments[] = $card;
            }
        }

        return count($availableAttachments) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

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
                        if ($hasRestrictions)
                        {
                            continue;
                        }

                        $availableAttachments[] = ["id" => $attachment->Id, "name" => $attachment->Name, "location" => $game->translate("Attached")];
                    }
                }
            }

            $discardPileName = $game->getPlayerDiscardDeckName($adversary->ControllerId);
            $cards = $game->theah->getCardObjectsAtLocation($discardPileName);
            $cards = array_filter($cards, fn($card) => $card instanceof Attachment);
            foreach ($cards as $card)
            {
                $availableAttachments[] = ["id" => $card->Id, "name" => $card->Name, "location" => $game->translate("Discard Pile")];
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
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);
            $owner = $this->getOwningCard($game->theah);

            if ($attachment->ControllerId != $adversary->ControllerId)
            {
                throw new \BgaUserException($game->translate("Card is not controlled by the Adversary."));
            }

            $discardPileName = $game->getPlayerDiscardDeckName($adversary->ControllerId);
            if ($attachment->Location != $discardPileName && $attachment->Location != $adversary->Location)
            {
                throw new \BgaUserException($game->translate("Card is not in the Adversary's Discard Pile or Attached to Adversary"));
            }

            [$discount, $explanations] = $game->theah->getEquipDiscount($actor, $attachment);
            $cost = $attachment->WealthCost - $discount;
            $handWealth = $game->handWealthCount($actor->ControllerId);
            if ($handWealth < $cost)
            {
                throw new \BgaUserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Attachment (%d with a discount of %d)."), $handWealth, $cost, $discount));
            }

            [$hasRestrictions, $restrictionExplanation] = $game->hasEquipRestrictions($actor, $attachment);
            if ($hasRestrictions)
            {
                throw new \BgaUserException($restrictionExplanation);
            }

            $attachment->ControllerId = $actor->ControllerId;
            $game->updateCardObjectInDb($attachment);
            $game->theah->addCardToWorld($attachment);

            if ($attachment->Location == $adversary->Location)
            {
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent($adversary->ControllerId, $adversary->Id, $attachment->Id);
                $game->theah->queueEvent($unequipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($adversary->ControllerId, $attachment->Id, $attachment->Location, $owner->Id);
                $game->theah->queueEvent($discardEvent);
            }

            $game->globals->set(Game::CHOSEN_ATTACHMENT, $attachment->Id);

            $game->globals->set(Game::CHOSEN_CARD_COST, $attachment->WealthCost);
            [$discount, $explanations] = $game->theah->getEquipDiscount($actor, $attachment);
            if ($discount != 0)
                $game->notify->player($actor->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);
            $game->globals->set(Game::DISCOUNT, $discount);
            $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);
    
            $discardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($adversary->ControllerId, $attachment->Id);
            $game->theah->queueEvent($discardEvent);

            $addedToHandEvent = EventFactory::createCardAddedToHandEvent($actor->ControllerId, $attachment->Id);
            $game->theah->queueEvent($addedToHandEvent);

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
    
            $musterEvent = EventFactory::createAttachmentEquippedEvent($actor->ControllerId, $actor->Id, $attachment->Id, $discount, $cost, $asAction = false, $explanations);
            $game->theah->queueEvent($musterEvent);
    
            $game->gamestate->nextState();
        }
    }

}