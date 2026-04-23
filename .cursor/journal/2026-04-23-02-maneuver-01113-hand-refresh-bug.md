# Maneuver_01113 — Attachment Stuck in Hand on Refresh

## Bug

After Maneuver_01113 (Take Control of Attachment) steals and equips an attachment, it disappears from the hand during live play (the `attachmentEquipped` notification removes it from the UI). But on page refresh, the attachment reappears in the hand.

## Root Cause

BGA tracks card locations in two places:
1. **BGA deck table** (`card_location` column) — read by `getCardsInLocation()`, used for page refresh state
2. **Serialized PHP object** (`card_serialized` column) — updated via `IsUpdated` flag

`EventCardAddedToHand` correctly calls `$deck->moveCard()` to set `card_location = 'hand'`. But `EventAttachmentEquipped` only updated the serialized PHP object's `Location` field — it did NOT call `$deck->moveCard()` to update the deck table. So after equipping, the deck table still said `hand`.

On page refresh, `getAllDatas` calls `getCardPropertiesInLocation(LOCATION_HAND)` which queries the deck table → attachment still showed up.

## WHY the handler didn't call moveCard (and why some callers did)

Some callers (FrameworkActionsTrait, Action_01167, Action_01180) had added `$deck->moveCard()` themselves before/after queuing the event. But this was a leaky pattern — the handler was leaving a critical DB update to the caller, and multiple cards (Maneuver_01113, Action_01113, Technique_01096, Maneuver_02054, Reaction_02037) were missing it.

## Fix — Centralized moveCard in EventHub handler

Added `$deck->moveCard($attachment->Id, $performer->Location, $event->playerId)` to the `EventAttachmentEquipped` handler in EventHub.php. This matches how `EventCardAddedToHand` handles its own `moveCard` at EventHub.php:416.

Removed the now-redundant caller-side `moveCard` calls from:
- FrameworkActionsTrait.php (line 694)
- Action_01167.php (line 358-359)
- Action_01180.php (line 317-318)

This also fixes the same latent bug in every other card that calls `createAttachmentEquippedEvent` without a `moveCard` (Action_01113, Technique_01096, Maneuver_02054, Reaction_02037, Action_02008).

## WHY centralize instead of adding moveCard to each caller

The EventHub handler already owns the location update (`$attachment->Location = $performer->Location`). Having the deck table sync live alongside it eliminates the entire class of "forgot to call moveCard" bugs. This is the same pattern `EventCardAddedToHand` uses — handler manages both stores.
