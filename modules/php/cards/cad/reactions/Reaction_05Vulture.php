<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_05Vulture extends CardReaction
{
    // WHY: Attachments unequip+discard BEFORE EventCharacterDestroyed (Character::unEquipAllAttachments).
    // Unequip clears AttachedToId; stash host while IsDying so non-destroy unequips do not pollute.
    /** @var array<int, int> attachmentId => former host characterId */
    private array $dyingHosts = [];

    private int $pendingHostId = 0;
    private int $pendingAttachmentId = 0;
    private string $stage = ''; // '', 'pickAttachment', 'pickHost', 'pay'
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

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Take control of destroyed character's attachment, paying all costs";
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'pay')
        {
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            return $base . sprintf(
                'Pay %d Wealth for %s — click cards in your hand. Paid so far: %d.',
                $this->paidCost,
                $attachment ? $attachment->Name : '',
                $this->paidWealth
            );
        }

        if ($this->stage === 'pickHost')
        {
            return $base . '${you} must choose a character you control to equip the attachment to: ';
        }

        if ($this->stage === 'pickAttachment')
        {
            return $base . '${you} must choose which attachment to take control of: ';
        }

        return $base . '${you} may take control of an equipped attachment from the destroyed character: ';
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return $array;
        }

        if ($this->stage === '' || $this->stage === 'pickAttachment')
        {
            $attachments = $this->getSalvageableAttachments($theah, $this->pendingHostId);
            foreach ($attachments as $attachment)
            {
                $array[] = $this->createButtonProperty(
                    $theah->game,
                    sprintf('Take %s', $attachment->Name),
                    'attachment_' . $attachment->Id
                );
            }
            $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');
        }

        if ($this->stage === 'pickHost')
        {
            $attachment = $theah->getAttachmentById($this->pendingAttachmentId);
            if ($attachment instanceof Attachment)
            {
                foreach ($this->getEligibleEquipTargets($theah, $owner, $attachment) as $character)
                {
                    $cost = $this->equipCost($theah, $character, $attachment);
                    $array[] = $this->createButtonProperty(
                        $theah->game,
                        sprintf('Equip to %s (cost %d)', $character->Name, $cost),
                        'equip_' . $character->Id
                    );
                }
            }
            $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');
        }

        if ($this->stage === 'pay')
        {
            $array[] = $this->createButtonProperty($theah->game, '< Back', 'back');
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
                $array[] = $this->createButtonProperty(
                    $theah->game,
                    sprintf('Pay with %s (+%d Wealth)', $card->Name, $wealth),
                    'pay-' . $card->Id
                );
            }

            $array[] = $this->createButtonProperty($theah->game, 'Pass', 'pass');
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->dyingHosts = [];
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
            unset($this->dyingHosts[$event->attachmentId]);
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
            return;
        }

        if ($event instanceof EventCharacterDestroyed && $this->isAvailable())
        {
            $this->tryOfferOnDestroy($event);
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

        if ($reactionId === 'pass')
        {
            // Pass does not burn the Reaction — destroy only fires once for this host.
            $this->clearHostStash($this->pendingHostId);
            $this->resetState($owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'attachment_'))
        {
            $this->handleAttachmentPick($game, $owner, $reactionId);
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
        if ($owner === null || $event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        // WHY: Plain Reaction (not City) — "this location" includes Home.
        if (! $event->theah->cardInPlay($owner))
        {
            return;
        }

        $character = $event->theah->getCharacterById($event->characterId);
        if (! ($character instanceof Character) || ! $character->IsDying || $character->Location != $owner->Location)
        {
            return;
        }

        $attachment = $event->theah->getAttachmentById($event->attachmentId);
        if (! $this->isSalvageableAttachment($attachment))
        {
            return;
        }

        $this->dyingHosts[$attachment->Id] = $character->Id;
        $owner->IsUpdated = true;
    }

    private function tryOfferOnDestroy(EventCharacterDestroyed $event): void
    {
        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null || $event->theah->game->characterIsInDiscardOrLocker($owner))
        {
            return;
        }

        if (! $event->theah->cardInPlay($owner))
        {
            return;
        }

        $destroyed = $event->theah->getCharacterById($event->characterId);
        if ($destroyed === null || $destroyed->Location != $owner->Location)
        {
            return;
        }

        $attachments = $this->getSalvageableAttachments($event->theah, $destroyed->Id);
        if (count($attachments) == 0)
        {
            return;
        }

        // Valid-target gate: at least one attachment has a payable host, else Pass-only noise.
        $anyPayable = false;
        foreach ($attachments as $attachment)
        {
            if (count($this->getEligibleEquipTargets($event->theah, $owner, $attachment)) > 0)
            {
                $anyPayable = true;
                break;
            }
        }
        if (! $anyPayable)
        {
            return;
        }

        $this->pendingHostId = $destroyed->Id;
        $this->pendingAttachmentId = 0;
        $this->chosenCharacterId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $this->paidDiscount = 0;
        $this->paidExplanations = '';
        $this->paidCost = 0;

        // WHY: Single attachment → skip the pick-attachment stage (Teens with one attachment).
        if (count($attachments) == 1)
        {
            $this->pendingAttachmentId = $attachments[0]->Id;
            $this->stage = 'pickHost';
        }
        else
        {
            $this->stage = 'pickAttachment';
        }

        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    /**
     * @return list<Attachment>
     */
    private function getSalvageableAttachments(Theah $theah, int $hostId): array
    {
        if ($hostId == 0)
        {
            return [];
        }

        $out = [];
        foreach ($this->dyingHosts as $attachmentId => $stashedHostId)
        {
            if ($stashedHostId != $hostId)
            {
                continue;
            }

            $attachment = $theah->getAttachmentById($attachmentId);
            if (! $this->isSalvageableAttachment($attachment))
            {
                continue;
            }

            if ($this->getDiscardPileOf($theah->game, $attachment) === null)
            {
                continue;
            }

            $out[] = $attachment;
        }

        return $out;
    }

    /**
     * Where the destroyed attachment now sits. null = not in a pile we can take it from.
     *
     * @return array{city: bool, playerId: int}|null
     */
    private function getDiscardPileOf(Game $game, Attachment $attachment): ?array
    {
        if ($attachment->Location == Game::LOCATION_CITY_DISCARD)
        {
            return ["city" => true, "playerId" => 0];
        }

        if ($attachment->OwnerId != 0 && $attachment->Location == $game->getPlayerDiscardDeckName($attachment->OwnerId))
        {
            return ["city" => false, "playerId" => (int)$attachment->OwnerId];
        }

        return null;
    }

    private function isSalvageableAttachment(?Attachment $attachment): bool
    {
        if (! ($attachment instanceof Attachment) || $attachment->FakeAttachment)
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
        if ($this->getDiscardPileOf($theah->game, $attachment) === null)
        {
            return [];
        }

        $out = [];
        $handWealth = $theah->game->handWealthCount($owner->ControllerId);
        // WHY: Equip hosts are only characters at Gusev's location (same scope as the destroy trigger).
        foreach ($theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId) as $character)
        {
            if (! ($character instanceof Character))
            {
                continue;
            }

            // WHY: Dying / locker hosts cannot keep the attachment (destroy pipeline mid-flight).
            if ($character->IsDying || $theah->game->characterIsInDiscardOrLocker($character))
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

    private function handleAttachmentPick(Game $game, Character $owner, string $reactionId): void
    {
        $attachmentId = (int) substr($reactionId, strlen('attachment_'));
        $attachments = $this->getSalvageableAttachments($game->theah, $this->pendingHostId);
        $match = null;
        foreach ($attachments as $attachment)
        {
            if ($attachment->Id == $attachmentId)
            {
                $match = $attachment;
                break;
            }
        }

        if ($match === null)
        {
            $this->resetState($owner);
            $game->gamestate->nextState("done");
            return;
        }

        if (count($this->getEligibleEquipTargets($game->theah, $owner, $match)) == 0)
        {
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $this->pendingAttachmentId = $match->Id;
        $this->stage = 'pickHost';
        $owner->IsUpdated = true;
        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleEquipPick(Game $game, Character $owner, string $reactionId): void
    {
        $characterId = (int) substr($reactionId, strlen('equip_'));
        $attachment = $game->theah->getAttachmentById($this->pendingAttachmentId);
        if (! ($attachment instanceof Attachment))
        {
            $this->resetState($owner);
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

        $this->stage = 'pickHost';
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
            $this->resetState($owner);
            $game->gamestate->nextState("done");
            return;
        }

        $pile = $this->getDiscardPileOf($game, $attachment);
        if ($pile === null)
        {
            $this->resetState($owner);
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

        // WHY: Take control before equip (Teens Smash and Grab). Equip sets ControllerId again.
        $attachment->ControllerId = $owner->ControllerId;
        $game->updateCardObjectInDb($attachment);
        $game->theah->addCardToWorld($attachment);

        $game->notify->all("message", '${reaction_inject_code}: ${player_name} takes control of ${attachment_inject_code}.', [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
            "attachment_inject_code" => $attachment->getInjectCode(),
        ]);

        if ($pile["city"])
        {
            $removedEvent = EventFactory::createCardRemovedFromCityDiscardPileEvent($owner->ControllerId, $attachment->Id);
        }
        else
        {
            $removedEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($pile["playerId"], $attachment->Id);
        }
        $game->theah->queueEvent($removedEvent);

        $actualTargetId = $attachment->getRequiredAttachTargetId($game->theah, $character->Id);

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

        unset($this->dyingHosts[$attachment->Id]);
        $this->clearHostStash($this->pendingHostId);
        $this->resetState($owner);
        $this->setUsed($game->theah, true);

        $game->gamestate->nextState("done");
    }

    private function clearHostStash(int $hostId): void
    {
        if ($hostId == 0)
        {
            return;
        }

        foreach (array_keys($this->dyingHosts) as $attachmentId)
        {
            if ($this->dyingHosts[$attachmentId] == $hostId)
            {
                unset($this->dyingHosts[$attachmentId]);
            }
        }
    }

    private function requeue(Game $game, Character $owner): void
    {
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resetState(?Character $owner): void
    {
        $this->stage = '';
        $this->pendingHostId = 0;
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
