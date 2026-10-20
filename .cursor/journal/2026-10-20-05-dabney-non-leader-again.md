# CAD Valeri — Action targets non-Leader again (3rd flip)

## Ask
Action_05DabneyUS01 changed to target a non-leader vice any character.

## Context
This is the Oct 9 change re-applied. Oct 12 reverted it ("any opposing character"); the user has now edited `_05DabneyUS01` Text back to "opposing non-<b>Leader</b>" (uncommitted diff). The print keeps flip-flopping between playtest versions, so the Text in `_05DabneyUS01` is the source of truth — if the Action and Text disagree, fix the Action to match the Text.

## Changes
- `Action_05DabneyUS01`: `! $c->hasTrait("Leader")` in `isAvailableToPlayer`, `getArgsFromAction` ids, and `isValidTargetForAbility` (new message). Name suffixed "(Non-Leader)".
- State picker copy -> "opposing non-Leader".
- Skill `references.md` lines for Valeri updated.
- Tests: added Leader-only-adjacent unavailability + validity check (the earlier flips had no tests, which is part of why they were easy to flip silently).

WHY all three gates: availability, UI ids, and server validation must agree (same as Action_02061 / 04057).

## Deploy
Action_05DabneyUS01, State_highDramaPhase05DabneyUS01 (Text file already changed by user).

## Notes
Full suite: 5026 pass. Didn't touch the Technique or the challenge-skip logic.
