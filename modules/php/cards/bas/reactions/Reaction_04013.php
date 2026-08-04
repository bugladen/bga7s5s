<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04013 extends CardReaction
{
    // WHY: Unequip clears AttachedToId before discard handlers run (Unequipped has
    // runEventHubAfterCards=false; Discard has true). Discard sourceId is not always
    // the host (destroy techniques pass the ability owner). Stash on unequip.
    /** @var array<int, int> attachmentId => former host characterId */
    private array $salvageableHosts = [];

    private ?Event $pendingDiscardEvent = null;
    private int $pendingAttachmentId = 0;
    private string $stage = ''; // '', 'pick', 'pay'
    private int $chosenCharacterId = 0;
    private int $paidDiscount = 0;
    private string $paidExplanations = '';
    private int $paidCost = 0;

    /** @var array<int> */
    private array $paidCardIds = [];
    private int $paidWealth = 0;
    private bool $paidHasWealthCard = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Equip discarded attachment to your character, paying all costs");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'pay')
        {
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            return $base . sprintf(
                $theah->game->translate('Pay %d Wealth for %s — click cards in your hand. Paid so far: %d.'),
                $this->paidCost,
                $attachment ? $attachment->Name : '',
                $this->paidWealth
            );
        }

        return $base . $theah->game->translate('${you} may equip the discarded attachment to your character at this location, paying all costs: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return $array;
        }

        if ($this->stage === 'pick')
        {
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            if ($attachment instanceof Attachment)
            {
                foreach ($this->getEligibleEquipTargets($theah, $owner, $attachment) as $character)
                {
                    $cost = $this->equipCost($theah, $character, $attachment);
                    $label = sprintf(
                        $theah->game->translate('Equip to %s (cost %d)'),
                        $character->Name,
                        $cost
                    );
                    $array[] = $this->createButtonProperty($theah->game, $label, 'equip_' . $character->Id);
                }
            }
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        }

        if ($this->stage === 'pay')
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('< Back'), 'back');
            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            foreach ($hand as $card)
            {
                if (in_array($card->Id, $this->paidCardIds, true))
                {
                    continue;
                }
                if ($card->Id == $this->pendingAttachmentId)
                {
                    continue;
                }
                if (! $this->wouldClickProduceValidPayment($card, $this->paidCost))
                {
                    continue;
                }

                $wealth = $card->hasTrait("Wealth") ? 2 : 1;
                $label = sprintf($theah->game->translate('Pay with %s (+%d Wealth)'), $card->Name, $wealth);
                $array[] = $this->createButtonProperty($theah->game, $label, 'pay-' . $card->Id);
            }

            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->salvageableHosts = [];
            $this->resetState($this->getOwningCharacter($event->theah));
            return;
        }

        if ($event instanceof EventAttachmentUnequipped && $this->isAvailable())
        {
            $this->noteUnequip($event);
            return;
        }

        if ($event instanceof EventAttachmentEquipped)
        {
            unset($this->salvageableHosts[$event->attachmentId]);
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
            return;
        }

        if (($event instanceof EventCardDiscardedFromPlay || $event instanceof EventCardAddedToCityDiscardPile)
            && $this->isAvailable()
            && ! $event->canceled)
        {
            $this->trySalvageDiscard($event);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'decline')
        {
            $this->declineAndRequeueDiscard($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'equip_'))
        {
            $this->handleEquipPick($game, $owner, $reactionId);
            return;
        }

        if (str_starts_with($reactionId, 'pay-'))
        {
            $this->handlePay($game, $owner, $reactionId);
            return;
        }

        if ($reactionId === 'back')
        {
            $this->handleBack($game, $owner);
            return;
        }

        $game->gamestate->nextState("done");
    }

    private function noteUnequip(EventAttachmentUnequipped $event): void
    {
        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $event->theah->cardInCity($owner))
        {
            return;
        }

        $character = $event->theah->getCharacterById($event->characterId);
        if (! ($character instanceof Character)
            || $character->ControllerId != $owner->ControllerId
            || $character->Location != $owner->Location)
        {
            return;
        }

        $attachment = $event->theah->getAttachmentById($event->attachmentId);
        if (! $this->isSalvageableAttachment($attachment))
        {
            return;
        }

        $this->salvageableHosts[$attachment->Id] = $character->Id;
        $owner->IsUpdated = true;
    }

    private function trySalvageDiscard(Event $event): void
    {
        if (! ($event instanceof EventCardDiscardedFromPlay || $event instanceof EventCardAddedToCityDiscardPile))
        {
            return;
        }

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || ! $event->theah->cardInCity($owner))
        {
            return;
        }

        if (in_array($owner->Id, $event->cancelDeclinedByCardIds, true))
        {
            return;
        }

        $attachment = $event->theah->getAttachmentById($event->cardId);
        if (! $this->isSalvageableAttachment($attachment))
        {
            return;
        }

        if ($event->fromLocation != $owner->Location)
        {
            return;
        }

        if (! $this->wasEquippedToYourCharacterHere($event->theah, $owner, $attachment))
        {
            return;
        }

        $eligible = $this->getEligibleEquipTargets($event->theah, $owner, $attachment);
        if (count($eligible) == 0)
        {
            return;
        }

        $clone = clone $event;
        unset($clone->theah);
        $this->pendingDiscardEvent = $clone;
        $this->pendingAttachmentId = $attachment->Id;
        $this->stage = 'pick';
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;
        $owner->IsUpdated = true;

        $event->canceled = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    private function wasEquippedToYourCharacterHere(Theah $theah, Character $owner, Attachment $attachment): bool
    {
        if (isset($this->salvageableHosts[$attachment->Id]))
        {
            $hostId = $this->salvageableHosts[$attachment->Id];
            $host = $theah->getCharacterById($hostId);
            // Host may already be dying/destroyed; location on the discard event is authoritative.
            return $host === null || $host->ControllerId == $owner->ControllerId;
        }

        // Fallback: still attached when discard fires (rare no-unequip path).
        if ($attachment->isAttached())
        {
            $host = $theah->getCharacterById($attachment->AttachedToId);
            return $host instanceof Character
                && $host->ControllerId == $owner->ControllerId
                && $host->Location == $owner->Location;
        }

        return false;
    }

    private function isSalvageableAttachment(?Attachment $attachment): bool
    {
        if (! ($attachment instanceof Attachment))
        {
            return false;
        }
        if ($attachment->FakeAttachment || $attachment->hasTrait("Artifact"))
        {
            return false;
        }
        return true;
    }

    /**
     * @return Character[]
     */
    private function getEligibleEquipTargets(Theah $theah, Character $owner, Attachment $attachment): array
    {
        $out = [];
        $handWealth = $theah->game->handWealthCount($owner->ControllerId);
        $characters = $theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
        foreach ($characters as $character)
        {
            if (! ($character instanceof Character))
            {
                continue;
            }
            if (! $attachment->canAttachTo($character))
            {
                continue;
            }
            [$hasRestrictions] = $theah->game->hasEquipRestrictions($character, $attachment);
            if ($hasRestrictions)
            {
                continue;
            }
            $cost = $this->equipCost($theah, $character, $attachment);
            if ($handWealth < $cost)
            {
                continue;
            }
            $out[] = $character;
        }
        return $out;
    }

    private function equipCost(Theah $theah, Character $performer, Attachment $attachment): int
    {
        [$discount] = $theah->getEquipDiscount($performer, $attachment);
        $cost = $attachment->WealthCost - $discount;
        return $cost < 0 ? 0 : $cost;
    }

    private function handleEquipPick(Game $game, Character $owner, string $reactionId): void
    {
        $characterId = (int) substr($reactionId, strlen('equip_'));
        $attachment = $game->theah->getAttachmentById($this->pendingAttachmentId);
        if (! ($attachment instanceof Attachment))
        {
            $this->declineAndRequeueDiscard($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $eligible = $this->getEligibleEquipTargets($game->theah, $owner, $attachment);
        $match = null;
        foreach ($eligible as $character)
        {
            if ($character->Id == $characterId)
            {
                $match = $character;
                break;
            }
        }
        if ($match === null)
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        [$discount, $explanations] = $game->theah->getEquipDiscount($match, $attachment);
        $cost = $attachment->WealthCost - $discount;
        if ($cost < 0)
        {
            $cost = 0;
        }

        $this->chosenCharacterId = $match->Id;
        $this->paidDiscount = $discount;
        $this->paidExplanations = is_string($explanations) ? $explanations : '';
        $this->paidCost = $cost;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $owner->IsUpdated = true;

        if ($cost <= 0)
        {
            $this->finalize($game, $owner);
            return;
        }

        $this->stage = 'pay';
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handlePay(Game $game, Character $owner, string $reactionId): void
    {
        if ($this->stage !== 'pay')
        {
            $game->gamestate->nextState("done");
            return;
        }

        $cardId = (int) substr($reactionId, strlen('pay-'));
        $card = $game->theah->getCardById($cardId);

        if ($card === null
            || $card->Location !== Game::LOCATION_HAND
            || $card->OwnerId !== $owner->ControllerId
            || in_array($card->Id, $this->paidCardIds, true)
            || $card->Id == $this->pendingAttachmentId)
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (! $this->wouldClickProduceValidPayment($card, $this->paidCost))
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $wealth = $card->hasTrait("Wealth") ? 2 : 1;
        $this->paidCardIds[] = $card->Id;
        $this->paidWealth += $wealth;
        if ($card->hasTrait("Wealth"))
        {
            $this->paidHasWealthCard = true;
        }
        $owner->IsUpdated = true;

        if ($this->isPaymentComplete($this->paidCost))
        {
            $this->finalize($game, $owner);
            return;
        }

        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleBack(Game $game, Character $owner): void
    {
        if ($this->stage === 'pay' && count($this->paidCardIds) > 0)
        {
            array_pop($this->paidCardIds);
            $this->recomputePaidTotals($game);
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $this->stage = 'pick';
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidCost = 0;
        $owner->IsUpdated = true;
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function finalize(Game $game, Character $owner): void
    {
        $attachment = $game->theah->getAttachmentById($this->pendingAttachmentId);
        $character = $game->theah->getCharacterById($this->chosenCharacterId);
        if (! ($attachment instanceof Attachment) || ! ($character instanceof Character))
        {
            $this->declineAndRequeueDiscard($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        foreach ($this->paidCardIds as $paidCardId)
        {
            $paidCard = $game->theah->getCardById($paidCardId);
            if ($paidCard === null)
            {
                continue;
            }
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($paidCard->OwnerId, $paidCard->Id, $sourceId = 0, $asPayment = true);
            $game->theah->queueEvent($discardEvent);
        }

        $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $character->Id);

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} equips ${attachment_inject_code} to ${character_inject_code} instead of discarding it.'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
            "attachment_inject_code" => $attachment->getInjectCode(),
            "character_inject_code" => $character->getInjectCode(),
        ]);

        $equipEvent = EventFactory::createAttachmentEquippedEvent(
            $owner->ControllerId,
            $actualTargetId,
            $attachment->Id,
            $this->paidDiscount,
            $this->paidCost,
            $asAction = true,
            $this->paidExplanations,
            false,
            $owner->Id,
            $this->Id
        );
        $game->theah->eventCheck($equipEvent);
        $game->theah->queueEvent($equipEvent);

        unset($this->salvageableHosts[$attachment->Id]);
        $this->pendingDiscardEvent = null;
        $this->resetState($owner);
        $this->setUsed($game->theah, true);

        $game->gamestate->nextState("done");
    }

    private function declineAndRequeueDiscard(Game $game, Character $owner): void
    {
        if ($this->pendingDiscardEvent !== null)
        {
            $this->pendingDiscardEvent->cancelDeclinedByCardIds[] = $owner->Id;
            $game->theah->queueEvent($this->pendingDiscardEvent);
        }
        unset($this->salvageableHosts[$this->pendingAttachmentId]);
        $this->pendingDiscardEvent = null;
        $this->resetState($owner);
    }

    private function requeue(Game $game, Character $owner): void
    {
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resetState(?Character $owner): void
    {
        $this->stage = '';
        $this->pendingAttachmentId = 0;
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    private function isPaymentComplete(int $cost): bool
    {
        if ($this->paidWealth == $cost)
        {
            return true;
        }
        if ($this->paidHasWealthCard && $this->paidWealth == $cost + 1)
        {
            return true;
        }
        return false;
    }

    private function wouldClickProduceValidPayment($card, int $cost): bool
    {
        $add = $card->hasTrait("Wealth") ? 2 : 1;
        $newWealth = $this->paidWealth + $add;
        $newHasWealth = $this->paidHasWealthCard || $card->hasTrait("Wealth");

        if ($newWealth < $cost)
        {
            return true;
        }
        if ($newWealth == $cost)
        {
            return true;
        }
        if ($newHasWealth && $newWealth == $cost + 1)
        {
            return true;
        }
        return false;
    }

    private function recomputePaidTotals(Game $game): void
    {
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        foreach ($this->paidCardIds as $paidCardId)
        {
            $card = $game->theah->getCardById($paidCardId);
            if ($card === null)
            {
                continue;
            }
            $this->paidWealth += $card->hasTrait("Wealth") ? 2 : 1;
            if ($card->hasTrait("Wealth"))
            {
                $this->paidHasWealthCard = true;
            }
        }
    }
}
