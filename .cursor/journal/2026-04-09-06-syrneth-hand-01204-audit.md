# Syrneth Hand (01204) Audit

## Card Text
> Traits: Artifact, Syrneth, Unique
> This card cannot be destroyed or moved from the equipped character.
> **Technique:** Wound the equipped performer • During the adversary's next round, their combat card has -2 [Parry].

## Bug found: Missing EventTechniqueCanceled handler

Technique_01204 sets `ReduceAdversaryParry = true` on `EventResolveTechnique` but had no `EventTechniqueCanceled` handler. Every other technique with a deferred state flag (01193, 01063, 01096, 01101, 01186, etc.) handles cancellation to reset its flag.

WHY this matters: Reactions like Reaction_01047 and Reaction_01146b can cancel a technique *after* it resolves. When they do, `deleteTechniqueEvents` removes queued events (e.g. the wound), but the in-memory `ReduceAdversaryParry` flag stays true. The adversary would get -2 Parry even though the technique was canceled.

**Fix**: Added `EventTechniqueCanceled` handler matching the pattern from Technique_01193 — resets `ReduceAdversaryParry = false` and marks attachment updated.

## Also fixed: Incorrect comment

Line 52 said "Reduce the opponent's Parry by 1" but the code does `removeParry(2)`. Fixed to say "by 2".

## What checks out

- **Protection**: `eventCheck` on `EventAttachmentUnequipped` prevents removal unless character is dying ✓
- **Duel-only gating**: `isAvailableToPlayer` checks `IN_DUEL` ✓
- **Wound on resolve**: Wounds the equipped character (performer), 1 wound, source is the attachment ✓
- **Parry reduction targeting**: `$character->Id == $event->adversaryId` correctly matches when the opponent's combat card stats are being calculated (the Syrneth Hand bearer is the adversary in that calculation) ✓
- **One-shot application**: Flag cleared after applying the reduction in `EventDuelCalculateCombatCardStats` ✓
- **Safety resets**: `EventDuelNewRound` resets flag when equipped character's turn starts; `EventDuelEnd` resets on duel end ✓
- **Base card properties**: CombatModifier 1, WealthCost 2, CityCardNumber 28 ✓
