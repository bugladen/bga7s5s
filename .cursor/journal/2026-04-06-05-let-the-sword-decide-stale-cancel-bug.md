# Let the Sword Decide (01146b) — Stale Cancel ID Bug

## The Bug

Eddie reported: Miyato & Ota (02043) activated Technique 02043a to copy Maneuver 02039 (Raise the Stakes). Opponent canceled the Technique with Let the Sword Decide's Reaction. The original maneuver was NOT supposed to be canceled, but at the start of the next round, no threat was added by the maneuver's delayed effect.

## Root Cause — Stale `ManeuverId` in Reaction_01146b

Reaction_01146b (Let the Sword Decide) responds to BOTH `EventManeuverActivated` and `EventTechniqueActivated`. It stores the ID of whichever it reacts to in `$this->ManeuverId` or `$this->TechniqueId`.

**The sequence that triggered the bug:**

1. Maneuver 02039 activated → Reaction_01146b fires, sets `$this->ManeuverId`
2. Opponent **declines** the maneuver cancel → `performReaction('decline')` runs, but `$this->ManeuverId` was **never cleared**
3. Technique 02043a activated → Reaction_01146b fires again, sets `$this->TechniqueId` — but `$this->ManeuverId` is **still set from step 1**
4. Opponent chooses **cancel** → `performReaction('cancel')` executes BOTH the `TechniqueId` block AND the `ManeuverId` block (both are non-empty)
5. The `ManeuverId` block fires `EventManeuverCanceled` for the original maneuver
6. Maneuver_02039 receives `EventManeuverCanceled` → sets `IsActive = false`
7. At end of round, `EventDuelEndOfRound` fires but Maneuver_02039 sees `IsActive = false` → no pending threat is set

## WHY This Was Hard to Find

The bug isn't in Technique_02043a or Maneuver_02039 — both are correct. It's in the reaction itself carrying stale state across multiple activations. The `handleEvent` method sets one ID but never clears the other, and the `performReaction('decline')` path never clears anything.

## The Fix — Two Changes in Reaction_01146b

1. **Clear the other ID on activation**: When `EventTechniqueActivated` sets `TechniqueId`, also clear `ManeuverId` (and vice versa). The reaction can only respond to one thing at a time.

2. **Clear both IDs on decline**: When the player declines, reset both `TechniqueId` and `ManeuverId` so no stale state persists.

Both fixes are needed for robustness — the activation fix prevents the double-cancel even if decline cleanup is missed, and the decline fix ensures clean state.

## Files Changed

- `modules/php/cards/_7s5s/reactions/Reaction_01146b.php`

## Impact

This bug could affect ANY scenario where Let the Sword Decide's reaction fires for a maneuver, the opponent declines, and then the same reaction fires for a technique (or vice versa) in the same duel round. The second cancel would inadvertently cancel both.
