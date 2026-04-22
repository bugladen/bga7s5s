# Reaction_02021: Wealth Attachment Choice Implementation

## What

Rewrote `Reaction_02021` (Grand Merchant Anghos's City Reaction) to use `EventEnteringPayState` instead of `EventCardDiscardedFromHand` as its trigger. The reaction now presents a list of attachments in the player's hand, lets them choose one to gain the Wealth trait, and handles the Locker redirect when that card is discarded as payment.

## WHY: addTrait("Wealth") Instead of a Global

Considered two approaches:
1. **Global `WEALTH_CONVERTED_CARD`**: Store the chosen card ID, modify 7 payment validation loops to check it, modify client Wealth calculation.
2. **`addTrait("Wealth")`**: Call `addTrait` on the chosen attachment. All 7 validation loops already check `hasTrait("Wealth")`. Client gets a `traitAdded` notification that updates `cardProperties[id].traits` -- same object reference used by `factionHand`, so `payForCard`'s `traits.includes('Wealth')` works automatically.

Chose #2 because it requires ZERO changes to validation loops or client-side code. The `addTrait` mechanism handles both server and client synchronization.

## WHY: getAttachmentById Works for Hand Cards

`Theah::getAttachmentById` calls `getCardById`, which checks `$this->cards` (in-memory city model) first, then falls back to `$this->db->getCardObject($cardId)`. Hand cards aren't in the city model but ARE found via the DB fallback. So `getAttachmentById` finds hand cards fine.

## Locker Handling

There's no generic "Wealth cards go to Locker when discarded as payment" handler in EventHub. Each card that does this (like Opulence `_01170`) handles it explicitly. The reaction centralizes this: when `EventCardDiscardedFromHand` fires with `AsPayment` matching the stored `attachmentId`, it queues a `createCardSentToLockerEvent`.

## Wealth Removal Timing (Eddie's revision)

Originally had cleanup at `EventDuskEndOfDay` (end of day). Eddie changed this to:

1. **On discard**: `removeTrait("Wealth")` immediately when `EventCardDiscardedFromHand` fires with `AsPayment` for the converted card, BEFORE queuing the Locker event. WHY: The card doesn't need the Wealth trait anymore once it's leaving the hand -- the Locker redirect is handled explicitly by queuing `createCardSentToLockerEvent`, not by the card "having" the Wealth trait. Removing it immediately prevents any downstream handlers from seeing a false Wealth trait on a card that only had it temporarily.

2. **End of player turn**: `EventPlayerTurnEnd` instead of `EventDuskEndOfDay`. WHY: Tighter scoping. If the card wasn't used as payment during the turn, the Wealth should be removed at turn boundary, not linger until dusk. A player could have multiple turns in a day, and the Wealth grant was specific to one payment opportunity.

Also added `$event->theah->cardInCity($owner)` check in the `EventEnteringPayState` handler -- the owning character must be in the city for the reaction to fire.

## Event Flow

1. `EventEnteringPayState` → reaction checks owner is in city + player has attachments in hand → queues reaction transition
2. Player sees one button per hand attachment + Decline
3. Player picks → `performReaction` calls `addTrait($game, 'Wealth')`, stores `attachmentId`, marks Used
4. Payment proceeds: card counts as 2 (server validation via `hasTrait`, client via shared traits array)
5. If discarded as payment → `EventCardDiscardedFromHand` → removeTrait("Wealth"), queue Locker event
6. If NOT discarded → `EventPlayerTurnEnd` → removeTrait if card still in hand

## Prerequisite

This depends on the EventEnteringPayState additions to recruit and brute flows (journal entry `2026-03-25-03`). Without those, the reaction won't fire for recruit/brute payments.
