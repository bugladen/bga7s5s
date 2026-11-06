# Claude (_05Dandy) Influence stuck after Collect

## Report

Collect Renown from his location (empty afterward) — Influence stayed at the penalized value instead of returning to printed 4.

## Cause

EventHub absolute-writes `ModifiedInfluence = NewInfluence` on `EventCharacterInfluenceModified`. Claude’s Renown sync writes Unclamped/Applied/chip immediately and queues InfMod for the client log; own InfMods (`Reason === inject code`) skip the Unclamped delta so the penalty is not applied twice.

If a leftover InfMod from *applying* the penalty (4→3) is still queued (Transition interrupt before it drained) and lands *after* Collect has already set Unclamped=4, Applied=0, chip=4, Hub paints the chip back to 3. Own-Reason skip leaves Unclamped at 4. Sync’s `desired === Applied` early return then refuses to touch the chip. Stuck at 3 on an empty location.

## Fix

1. After every InfMod on Claude, `reassertChipFromUnclamped` — if chip ≠ max(0, Unclamped), rewrite chip and queue a correction log. Do **not** call `syncRenownPenalty` from InfMod: Location can still be the old city while a home restore already cleared Applied via `toLocation` (Éventail stacks InfMod before CardMoved).
2. Sync early-return also requires chip already matches Unclamped (same drift heal).
3. `repairAttachmentInfluence` (buildCity) also runs `syncRenownPenalty` so in-flight tables heal on load.

## Tests

`stale penalty InfMod cannot stick after renown is cleared`. Eventail dusk tests now seed location Renown=1 so Applied matches the board (InfMod path used to ignore that inconsistency).

## Do not

- Re-sync Renown from `$this->Location` inside the InfMod handler (breaks Éventail dusk order).
- “Simplify” back to trusting Hub’s absolute NewInfluence without Unclamped as source of truth for the chip after own InfMods.
