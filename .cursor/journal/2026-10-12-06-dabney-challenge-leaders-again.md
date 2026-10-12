# CAD Valeri — Action can challenge Leaders again

## Ask
Action_05DabneyUS01 can once again target leaders. Any opposing character can be targeted.

## Context
Oct 9 (`2026-10-09-05-dabney-non-leader-challenge.md`) gated challenge to non-Leaders for PTv0.4 print. Card `_05DabneyUS01` Text already back to "opposing Character". Revert the Action gate to match.

## Changes
- `Action_05DabneyUS01`: drop `!hasTrait("Leader")` from availability / args ids / `isValidTargetForAbility`. Name back to without "Non-Leader".
- State picker: "opposing character" again.
- Skill refs (pattern-f + references): Leaders allowed.

WHY revert all three gates together: same as when we added them — availability, UI ids, and server validation must agree.

## Deploy
Action_05DabneyUS01, State_highDramaPhase05DabneyUS01.
