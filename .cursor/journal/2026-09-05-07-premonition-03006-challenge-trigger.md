# Premonition Reaction_03006 — challenge target didn't trigger

## Bug
Strega Reaction on Premonition (`_03006`) never offered after an opponent issued a basic challenge that chose one of your characters as target.

## Root cause (two stacked)

1. **`sourceId = 0` on basic challenge.** `actHighDramaChallengeActionStart` sets `CHOSEN_ACTION` / `TRANSITION_INTERNAL_ID` to `BasicChallenge` but never sets `TRANSITION_SOURCE_ID`. `stIssueChallenge` therefore emits `EventChallengeIssued` with `sourceId = 0`.

   `maybeTrigger` did:
   ```php
   $opposingPlayerId = $source ? $source->ControllerId : 0;
   if ($opposingPlayerId == 0 ...) return;
   ```
   So sourceless challenges always bailed. Same gap Reaction_01014 already fixed (journal `2026-03-08-01-basic-challenge-action.md`) via `$event->playerId` fallback — Premonition never got that fix.

2. **Technique overwrite of `abilityId`.** `actHighDramaChallengeActionTechniqueActivated` sets `TRANSITION_INTERNAL_ID` to the technique id before calling `stIssueChallenge`. So even when (1) is fixed, `sourceAbilityTargetsCharacters` looks up the *technique* (not `BasicChallenge`) and usually returns false — challenge with an opening technique would still miss.

## Fix
In `Reaction_03006`:
- Pass `$event->playerId` into `maybeTrigger` as `$initiatingPlayerId` when the event has it; use it when source card is null.
- For `EventChallengeIssued` specifically, pass `$requireCharacterTargetingAbility = false`. WHY: a challenge always chooses a defender character; the interface gate is the wrong tool once technique activation mutates `abilityId`.

Did **not** change `stIssueChallenge` / technique globals — that would be a broader framework fix and Reaction_01014 already works around sourceless challenges the same way for opponent id. Skipping the interface check only on the challenge event keeps other trigger paths gated.

## Not changed
Card-sourced challenges (city actions etc. with real `sourceId`) already worked via `$source->ControllerId`. This only unblocks basic / sourceless challenge targeting.
