# RiskClone Persists After Maryam Cancels Attachment Equipping

## The Bug

When Action_02008 (Fate's Kiss) creates a `_02008_RiskClone` and attaches it to an opponent's character, but Maryam (_01186) cancels the `EventAttachmentEquipping`, the face-down clone card remained visible at the character's location on page refresh.

## Root Cause

The clone was created via `createCardInLocation()` which inserts into the DB but does NOT add the card to `Theah::$cards` (the in-memory card collection). The card was supposed to be added to the world later by `EventAttachmentEquipped` handler (line 167 of EventHub.php: `$theah->addCardToWorld($attachment)`), but since Maryam canceled `EventAttachmentEquipping`, `EventAttachmentEquipped` never fired.

When Maryam's cleanup events (`CardRemovedFromPlay`, `CardDiscardedFromPlay`) processed, the EventHub handlers correctly called `$deck->moveCard()` (updating `card_location` in the BGA deck table) and set `$card->IsUpdated = true`. But since the clone wasn't in `$this->cards`, the `runEvents()` update loop never called `updateCardObjectInDb()` to save the updated `Location` to `card_serialized`.

Result: `card_location` = discard pile, but `card_serialized.Location` = character's location (stale). On page refresh, `buildCity()` loads cards by `card_location` but deserializes `card_serialized`, and `getCardPropertiesAtLocation()` checks the deserialized `Location` property - so the clone appeared at the character's location.

## Fix

Added `$game->theah->addCardToWorld($card)` immediately after `createCardInLocation()` in Action_02008. This ensures the clone is in `$this->cards` so the event system properly updates and persists its Location through the `IsUpdated` mechanism.

This is safe for the non-canceled path too: `EventAttachmentEquipped` handler's `addCardToWorld()` just overwrites the same entry.

## Dual Storage Gotcha

This codebase has two parallel storage mechanisms for card location:
- `card_location` column (BGA deck component, updated by `$deck->moveCard()`)
- `card_serialized` blob containing the PHP object with `Location` property (updated by `updateCardObjectInDb()`)

They must stay in sync. Cards not in `Theah::$cards` won't get their serialized form updated by the event loop, even if `$deck->moveCard()` correctly updates `card_location`. Any card that participates in events should be in `$this->cards`.
