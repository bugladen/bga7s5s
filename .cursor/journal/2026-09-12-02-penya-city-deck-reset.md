# Penya (03cd01) — reset on shuffle into City Deck

## Bug

City Forced shuffles Penya via `createCardRemovedFromPlayEvent` → `LOCATION_CITY_DECK`. `EventCardRemovedFromPlay` only moved the deck row / Location. Unlike destroy and city-discard, it did **not** recreate the Character. So after Forced:

- `ControllerId` (and `OwnerId` if set) stayed on the card in the City Deck
- Wounds / modified stats / engagement / conditions survived for a later muster
- Deck `location_arg` was the old ControllerId (city deck rows should be 0)

## Fix

1. **EventHub `EventCardRemovedFromPlay`**: when destination is City Deck and the card is a Character, recreate via `instantiateCard` (same pattern as destroy / `EventCardAddedToCityDiscardPile`), set `ControllerId = 0`, `OwnerId = 0`, `Location = CITY_DECK`, `Engaged = false`. Move with `locationArg = 0` for City Deck.

2. **`_03cd01::triggerForcedAbility`**: `unEquipAllAttachments` before queuing remove. WHY: recreate wipes `Attachments[]`; without unequip, equipped cards orphan in the world. Matches wound→destroy.

Shuffle listener on Penya still works — EventHub recreates first (`addCardToWorld`), then cards' `handleEvent` runs on the fresh instance.

## Why not only in `_03cd01`

Central path matches destroy/city-discard. Penya is currently the only Character using remove-from-play → City Deck, but the memory wipe belongs with the move, not a one-off card quirk.

## Unfinished / watch

Duel-mid-Forced edge case (duel DB already created when Penya leaves) still open from the original Penya journal.
