# Unyielding Loyalty (_01032) Audit

## Card Text
> Reaction: When one or more of your cards is targeted, destroy your Red Hand or discard a Thug from your hand • Cancel the effects.

Vodacce Risk card. Riposte=2, Parry=dashed, Thrust=1, WealthCost=1. Traits: Camaraderie, Zeal. Implements `IHasReactions` with `Reaction_01032`.

## Bug 1: Stale `inHandThug` flag leak

### What was wrong
In `performReaction`, when a player passes on the Red Hand option, the code unconditionally set `inHandThug = true` before checking if thugs actually exist in hand:

```php
$this->inPlayRedHand = false;
$this->inHandThug = true;  // Set BEFORE checking
if ($this->thugsInHand(...)) { ... }
else { $this->releaseEvent($game); $this->skipNextEvent = true; }
// inHandThug stays true even when no thugs!
```

If `thugsInHand()` returned false, the event was released and `skipNextEvent` set, but `inHandThug` leaked as `true`. On the next event trigger, `handleEvent` would set `inPlayRedHand = true` without clearing the stale `inHandThug`, causing both flags to be true simultaneously. This meant:
- `getReactionButtonProperties` would show BOTH Red Hand AND Thug buttons at once
- `EventRiskReactionTriggered` handler has two non-exclusive `if` blocks — if both flags are true and player selects "destroy-123", the `inHandThug` block would try to parse "destroy-123" as "discard-destroy-123" and crash on `getCardById`

### Fix
Only set `inHandThug = true` when thugs actually exist. Moved the assignment inside the `if ($this->thugsInHand(...))` branch.

### WHY
The sequential Red Hand → Thug flow requires clean state transitions. Setting a flag optimistically before validating preconditions is a classic source of stale state bugs. The fix makes the state machine deterministic: exactly one flag is true at any time.

## Bug 2: `shouldReactToEvent` only checked `IAbilityThatTargetsCharacters`

### What was wrong
Card text says "your **cards** is targeted" — not just characters. The `shouldReactToEvent` method only checked `IAbilityThatTargetsCharacters`, missing abilities that implement `IAbilityThatTargetsCards`. This interface is used by abilities that target non-character cards (attachments, risks, etc.).

### Fix
Added `|| $ability instanceof IAbilityThatTargetsCards` to the check.

### WHY
The card text is explicitly broader than characters. Even though many `IAbilityThatTargetsCards` abilities generate events outside the listened set (like `EventAttachmentUnequippedEvent`), some card-targeting abilities DO generate the listened events (e.g., engaging/moving cards). The check should match the card text for correctness and future-proofing.

## Bug 3: Missing `getInPlayActionById` fallback

### What was wrong
The sibling reaction `Reaction_01014` (Vittoria) has a fallback in `shouldReactToEvent`: if `$source->getAbilityById()` returns null, it tries `$theah->getInPlayActionById()`. Reaction_01032 didn't have this fallback, meaning in-play actions (abilities that live on cards but are accessed through a different lookup path) could silently fail to trigger the reaction.

### Fix
Added the `getInPlayActionById` fallback, matching Reaction_01014's pattern.

### WHY
In-play actions use a different registry than card abilities. Without the fallback, the `getAbilityById` lookup returns null, the check fails, and the reaction doesn't fire — even when the ability clearly targets your cards.

## Bug 4: Missing hand location check

### What was wrong
As a `RiskReaction`, the owning card (Unyielding Loyalty) must be in the player's hand to trigger. Other `RiskReaction` implementations like `Reaction_01080` and `Reaction_01137` check `$owner->Location == Game::LOCATION_HAND` before proceeding. Reaction_01032 was missing this check across all 6 event handlers, meaning the reaction could theoretically trigger even if the card was in the discard pile or elsewhere.

### Fix
Added `if ($owner->Location == Game::LOCATION_HAND)` wrapping the inner logic of each event handler, matching the established pattern from other RiskReaction implementations.

### WHY
Risk reactions are played from the hand. Without this guard, the reaction could fire when the card is in an unexpected location (e.g., after being discarded as part of paying for another card). The hand check is a standard guard for all RiskReaction implementations.

## Verified Correct

### Trigger: "When one or more of your cards is targeted"
- Listens for 6 event types: `EventCardEngaged`, `EventCardEngarded`, `EventCardMoving`, `EventCharacterBeingWounded`, `EventCharacterBeingHealed`, `EventChallengeIssued`
- Each handler checks `$owner->Location == Game::LOCATION_HAND` — confirms the risk card is in hand
- Each handler checks `$owner->ControllerId == $card->ControllerId` — confirms the targeted card belongs to the reaction owner
- `shouldReactToEvent` validates the source ability is a targeting ability

### Cost: "destroy your Red Hand or discard a Thug from your hand"
- Red Hand: `getCharactersInPlayByPlayerId` filtered by `hasTrait("Red Hand")` → destroy via `createCharacterDestroyedEvent`
- Thug: `getCardObjectsAtLocation(LOCATION_HAND)` filtered by `hasTrait("Thug")` → discard via `createCardDiscardedFromHandEvent`
- Sequential OR: Red Hand offered first, Pass → Thugs offered, Pass again → event released

### Effect: "Cancel the effects"
- `$event->canceled = true` on the original event
- `clearEvents()` nulls all captured events when cost is paid
- `releaseEvent()` re-queues events if player passes on both options

## Design Notes
- Unlike Reaction_01014 (Vittoria), this reaction does NOT check that the source is from a different player. This means theoretically your own targeting abilities could trigger it. I left this as-is since it's been working and may be intentional (the card text doesn't specify "by an opponent").
- Event coverage doesn't include attachment-specific events (e.g., `EventAttachmentUnequippedEvent`). If an opponent destroys your attachment via a card-targeting ability that only generates attachment events, this reaction won't fire. This is an architectural limitation, not a simple bug to fix.
