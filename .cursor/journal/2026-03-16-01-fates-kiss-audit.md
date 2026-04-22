# Fate's Kiss (02008) Audit & Fix

## What Happened

Eddie asked me to audit card 02008 (Fate's Kiss) against its card text. Found a real bug in `_02008_RiskClone.php`.

## The Bug

The clone was listening for `EventCardDiscardedFromPlay` to trigger returning the original risk to hand. This fires on ANY discard of the clone — not just character destruction. Card text says "When that character is destroyed," which is a more specific trigger.

## Why `EventCharacterDestroyed` Is Correct

The key insight is event ordering. When a character dies:
1. `unEquipAllAttachments()` queues `EventAttachmentUnequipped` + `EventCardDiscardedFromPlay` for each attachment
2. `EventCharacterDestroyed` is queued after those
3. Events process FIFO in `runEvents()` — so the clone handles `EventCardDiscardedFromPlay` (cleanup only) before `EventCharacterDestroyed` (return risk)
4. The clone stays in `$this->cards` through the entire cycle because `buildCity()` only runs once per cycle

For the edge case where the clone is removed by other means and the character is destroyed later: after permanent hiding, `buildCity()` won't load the clone (it only loads specific locations, not `LOCATION_PERMANENTLY_HIDDEN`), so no stale triggering.

## Changes Made

- `_02008_RiskClone.php`: Added `TargetCharacterId` property, split the single `EventCardDiscardedFromPlay` handler into two: cleanup on discard, risk-return on `EventCharacterDestroyed`
- `Action_02008.php`: Set `TargetCharacterId` on the clone at creation time

## Design Decision: Using ControllerId for the hand event

Used `$this->ControllerId` instead of `$event->ownerId` (which was used before from `EventCardDiscardedFromPlay`). Both resolve to the same player — the one who played Fate's Kiss. But `$this->ControllerId` is more direct and doesn't depend on event properties.

## Everything Else Was Fine

- `_02008.php`: Correct card setup (Vodacce, Sorcery/Sorte traits, Risk class)
- `Action_02008.php`: Sorcerer+Strega check, discard pile filtering, two-phase state machine (pick risk, then pick character), sorcery start/end events all correct
- Both state files: Clean, correct transitions
- The face-down placement via `_02008_RiskClone` as a `FactionAttachment` with `FaceDown = true` is elegant
