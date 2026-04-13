# Makepeace Botwighte (01092) Audit

Card text: "When a character opposing Makepeace equips a card, it gains +1 cost. **City Action:** Target an opposing engaged character with equal or less [Influence] • Move them Home."

## Passive: +1 equip cost for opposing characters

Implemented in `_01092::getEquipDiscount`. Returns `$discount -= 1` (negative discount = cost increase). Checks: opposing player, same location, performer in city. Correct — if Makepeace is at home, no city character can match the location, so the implicit city check holds.

## City Action: Move opposing engaged character home

Two bugs found and fixed in `Action_01092`:

### Bug 1: Missing `cardInCity` check in `isAvailableToPlayer`
This is labeled "City Action" but `isAvailableToPlayer` never checked `$theah->cardInCity($makepeace)`. Compare with `Action_01091` (Madre Dolores) which correctly gates on `cardInCity`. In practice this is low-impact since at-home locations differ per player, so there'd rarely be valid targets — but it's still a correctness gap.

### Bug 2: Missing Influence check in `isValidTargetForAbility`
The card requires "equal or less [Influence]" but server-side validation in `isValidTargetForAbility` didn't enforce it. Both `isAvailableToPlayer` and `getArgsFromAction` correctly filter by `ModifiedInfluence <= makepeace.ModifiedInfluence`, so the UI would only show valid targets — but a crafted API request could bypass the constraint since `actFromActionWithId` relies on `isValidTargetForAbility` for validation.

Added the check: `$character->ModifiedInfluence > $makepeace->ModifiedInfluence` → reject.

## State file

`State_highDramaPhase01092` — standard active player state, transitions to events after character chosen. Clean.
