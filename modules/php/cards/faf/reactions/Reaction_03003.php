<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03003 extends CardReaction
{
    // Reaction-instance state. Persists across reaction state re-entries so a
    // multi-stage flow (pick → pay → finalize) can run inside the standard
    // playerReaction loop. IsUpdated must be set on the owner whenever this
    // state changes so the framework persists the new field values.
    private int $destroyedThugId = 0;
    private string $stage = ''; // '', 'pick', 'pay'
    private int $chosenThugId = 0;
    private string $chosenThugLocation = '';

    // Wealth-payment running state — tracked incrementally as the player
    // clicks one card-button at a time.
    private array $paidCardIds = [];   // ordered list of card ids the player has selected so far
    private int $paidWealth = 0;       // running sum of wealth value of the paid cards
    private bool $paidHasWealthCard = false; // true if any paid card has the "Wealth" trait

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Put a different Thug into play at your Home, at -1 cost");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);

        if ($this->stage === 'pick')
        {
            return $base . $theah->game->translate('${you} may put a different Thug into play at your Home, at -1 cost:');
        }

        if ($this->stage === 'pay')
        {
            $thug = $theah->getCardById($this->chosenThugId);
            $cost = $this->thugDiscountedCost($thug);
            return $base . sprintf(
                $theah->game->translate('Pay %d Wealth for %s — click cards in your hand. Paid so far: %d.'),
                $cost,
                $thug ? $thug->Name : '',
                $this->paidWealth
            );
        }

        return $base;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->stage === 'pick')
        {
            foreach ($this->getEligibleThugs($theah, 'hand') as $thug)
            {
                $cost = $this->thugDiscountedCost($thug);
                $label = sprintf($theah->game->translate('Hand: %s (cost %d)'), $thug->Name, $cost);
                $array[] = $this->createButtonProperty($theah->game, $label, "pickHand-{$thug->Id}");
            }

            foreach ($this->getEligibleThugs($theah, 'discard') as $thug)
            {
                $cost = $this->thugDiscountedCost($thug);
                $label = sprintf($theah->game->translate('Discard: %s (cost %d)'), $thug->Name, $cost);
                $array[] = $this->createButtonProperty($theah->game, $label, "pickDiscard-{$thug->Id}");
            }
        }

        if ($this->stage === 'pay')
        {
            $thug = $theah->getCardById($this->chosenThugId);
            $cost = $this->thugDiscountedCost($thug);
            $owner = $this->getOwningCharacter($theah);

            $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
            foreach ($hand as $card)
            {
                // Exclude cards already selected for this payment, and exclude
                // the chosen Thug itself when it's the one being paid from hand.
                if (in_array($card->Id, $this->paidCardIds, true))
                {
                    continue;
                }
                if ($card->Id == $this->chosenThugId)
                {
                    continue;
                }
                if (! $this->wouldClickProduceValidPayment($card, $cost))
                {
                    continue;
                }

                $wealth = $card->hasTrait("Wealth") ? 2 : 1;
                $label = sprintf($theah->game->translate('Pay with %s (+%d Wealth)'), $card->Name, $wealth);
                $array[] = $this->createButtonProperty($theah->game, $label, "pay-{$card->Id}");
            }

            // < Back undoes the most recent paid card, or returns to the picker
            // if no cards have been paid yet.
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('< Back'), 'back');
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);

            if ($event->theah->game->characterIsInDiscardOrLocker($owner))
            {
                return;
            }

            if (! $event->theah->cardInCity($owner))
            {
                return;
            }

            $destroyed = $event->theah->getCharacterById($event->characterId);
            if ($destroyed === null)
            {
                return;
            }

            if (! $destroyed->hasTrait("Thug"))
            {
                return;
            }

            if ($destroyed->ControllerId != $owner->ControllerId)
            {
                return;
            }

            $this->destroyedThugId = $destroyed->Id;

            $eligibleHand = $this->getEligibleThugs($event->theah, 'hand');
            $eligibleDiscard = $this->getEligibleThugs($event->theah, 'discard');
            if (count($eligibleHand) == 0 && count($eligibleDiscard) == 0)
            {
                return;
            }

            $this->stage = 'pick';
            $this->chosenThugId = 0;
            $this->chosenThugLocation = '';
            $this->paidCardIds = [];
            $this->paidWealth = 0;
            $this->paidHasWealthCard = false;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId === 'decline')
        {
            // Abort entirely. No cards are discarded — the player keeps anything
            // they had tentatively selected during the 'pay' stage because we
            // haven't queued any discard events yet.
            $this->resetState($owner);
            $this->setUsed($game->theah, true);
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'pickHand-') || str_starts_with($reactionId, 'pickDiscard-'))
        {
            $this->handlePick($game, $owner, $reactionId);
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

    private function handlePick(Game $game, $owner, string $reactionId): void
    {
        $fromHand = str_starts_with($reactionId, 'pickHand-');
        $prefix = $fromHand ? 'pickHand-' : 'pickDiscard-';
        $thugId = (int)substr($reactionId, strlen($prefix));

        $thug = $game->theah->getCardById($thugId);
        if ($thug === null || ! $thug->hasTrait("Thug") || $thug->Id == $this->destroyedThugId)
        {
            $this->resetState($owner);
            $this->setUsed($game->theah, true);
            $game->gamestate->nextState("done");
            return;
        }

        $expectedLocation = $fromHand
            ? Game::LOCATION_HAND
            : $game->getPlayerDiscardDeckName($owner->ControllerId);

        if ($thug->Location !== $expectedLocation || $thug->OwnerId !== $owner->ControllerId)
        {
            $this->resetState($owner);
            $this->setUsed($game->theah, true);
            $game->gamestate->nextState("done");
            return;
        }

        $this->chosenThugId = $thug->Id;
        $this->chosenThugLocation = $expectedLocation;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;

        $cost = $this->thugDiscountedCost($thug);
        if ($cost <= 0)
        {
            // Free Thug — go directly to finalization, skipping the 'pay' stage.
            $this->finalize($game, $owner);
            return;
        }

        $this->stage = 'pay';
        $owner->IsUpdated = true;

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

        $cardId = (int)substr($reactionId, strlen('pay-'));
        $card = $game->theah->getCardById($cardId);

        if ($card === null
            || $card->Location !== Game::LOCATION_HAND
            || $card->OwnerId !== $owner->ControllerId
            || in_array($card->Id, $this->paidCardIds, true)
            || $card->Id == $this->chosenThugId)
        {
            // Invalid pay click — re-show the same stage. Don't abort the
            // reaction; the player may have hit a stale button.
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        $thug = $game->theah->getCardById($this->chosenThugId);
        $cost = $this->thugDiscountedCost($thug);

        if (! $this->wouldClickProduceValidPayment($card, $cost))
        {
            // Defensive: button shouldn't have been shown if invalid.
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

        if ($this->isPaymentComplete($cost))
        {
            $this->finalize($game, $owner);
            return;
        }

        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function handleBack(Game $game, $owner): void
    {
        if ($this->stage === 'pay' && count($this->paidCardIds) > 0)
        {
            // Undo the most recently selected card. Recompute the wealth/Wealth
            // flag from scratch instead of trying to reverse the increment so
            // the running state stays consistent regardless of click order.
            array_pop($this->paidCardIds);
            $this->recomputePaidTotals($game);
            $owner->IsUpdated = true;
            $this->requeue($game, $owner);
            $game->gamestate->nextState("done");
            return;
        }

        // Otherwise (no paid cards, or a stray Back from 'pick'), return to the
        // Thug picker.
        $this->stage = 'pick';
        $this->chosenThugId = 0;
        $this->chosenThugLocation = '';
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $owner->IsUpdated = true;

        $this->requeue($game, $owner);
        $game->gamestate->nextState("done");
    }

    private function finalize(Game $game, $owner): void
    {
        $thug = $game->theah->getCardById($this->chosenThugId);
        if ($thug === null)
        {
            $this->resetState($owner);
            $this->setUsed($game->theah, true);
            $game->gamestate->nextState("done");
            return;
        }

        $cost = $this->thugDiscountedCost($thug);

        $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} puts ${thug_inject_code} into play at Home, at -1 cost (paid ${paid} Wealth, cost ${cost}).'), [
            "reaction_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($owner->ControllerId),
            "thug_inject_code" => $thug->getInjectCode(),
            "paid" => $this->paidWealth,
            "cost" => $cost,
        ]);

        // Discard each card the player paid with. Done before muster so the
        // hand state is consistent when the muster event fires.
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

        // WHY: When the Thug comes from the discard pile, fire the removal
        // event first so downstream listeners can observe the card leaving the
        // pile before it gets mustered into play.
        if ($this->chosenThugLocation === $game->getPlayerDiscardDeckName($owner->ControllerId))
        {
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($owner->ControllerId, $thug->Id);
            $game->theah->queueEvent($removeEvent);
        }

        $musterEvent = EventFactory::createCharacterMusteredEvent($owner->ControllerId, $thug->Id, Game::LOCATION_PLAYER_HOME);
        $game->theah->queueEvent($musterEvent);

        $this->resetState($owner);
        $this->setUsed($game->theah, true);

        $game->gamestate->nextState("done");
    }

    private function requeue(Game $game, $owner): void
    {
        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($transition);
    }

    private function resetState($owner): void
    {
        $this->stage = '';
        $this->chosenThugId = 0;
        $this->chosenThugLocation = '';
        $this->destroyedThugId = 0;
        $this->paidCardIds = [];
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        $owner->IsUpdated = true;
    }

    /**
     * Returns Thugs (excluding the just-destroyed one) that the player owns
     * at the specified source: 'hand' or 'discard'.
     */
    private function getEligibleThugs(Theah $theah, string $source): array
    {
        $owner = $this->getOwningCharacter($theah);
        $location = $source === 'hand'
            ? Game::LOCATION_HAND
            : $theah->game->getPlayerDiscardDeckName($owner->ControllerId);

        $cards = $theah->getCardObjectsAtLocation($location, $owner->ControllerId);
        $thugs = array_filter($cards, fn($card) => $card->hasTrait("Thug") && $card->Id != $this->destroyedThugId);
        return array_values($thugs);
    }

    private function thugDiscountedCost($thug): int
    {
        if ($thug === null)
        {
            return 0;
        }
        $base = $thug instanceof IWealthCost ? $thug->getWealthCost() : (property_exists($thug, 'WealthCost') ? (int)$thug->WealthCost : 0);
        $cost = $base - 1;
        return $cost < 0 ? 0 : $cost;
    }

    /**
     * Mirrors UtilitiesTrait::isValidWealthPayment. A payment is complete when
     * it exactly matches the cost OR overpays by 1 using at least one Wealth
     * card. Anything else means the player must keep clicking.
     */
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

    /**
     * Decides whether clicking $card would leave the running payment in a
     * still-recoverable state — either short of cost, exactly at cost, or
     * exactly cost+1 with at least one Wealth card in the pile. Anything that
     * would over-pay invalidly is filtered out of the button list.
     */
    private function wouldClickProduceValidPayment($card, int $cost): bool
    {
        $cardWealth = $card->hasTrait("Wealth") ? 2 : 1;
        $newPaid = $this->paidWealth + $cardWealth;
        $newHasWealth = $this->paidHasWealthCard || $card->hasTrait("Wealth");

        if ($newPaid < $cost)
        {
            return true;
        }
        if ($newPaid == $cost)
        {
            return true;
        }
        if ($newPaid == $cost + 1 && $newHasWealth)
        {
            return true;
        }
        return false;
    }

    private function recomputePaidTotals(Game $game): void
    {
        $this->paidWealth = 0;
        $this->paidHasWealthCard = false;
        foreach ($this->paidCardIds as $cardId)
        {
            $card = $game->theah->getCardById($cardId);
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
