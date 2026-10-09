# CAD Valeri — Action cannot challenge Leaders (PTv0.4)

## Ask
Action_05DabneyUS01 now cannot challenge a leader.

## Context
User already updated `_05DabneyUS01` Text (Halloween commit) from "opposing character" → "opposing non-Leader" to match image_store `05DabneyUS01_0.4.jpg`. Action still offered Leaders in availability / args / validation.

## Changes
- `Action_05DabneyUS01`: filter `!hasTrait("Leader")` in `isAvailableToPlayer`, `getArgsFromAction` ids, `isValidTargetForAbility` (04057 message). Name notes Non-Leader.
- Fixed broken Text HTML `non-</b>Leader</b>` → `non-<b>Leader</b>`.
- State picker copy: "opposing non-Leader".
- Skill refs (pattern-f + references) note non-Leader target.

WHY gate in all three places: same pattern as Action_02061 / 04057 — availability false when only Leaders adjacent; UI ids exclude; server reject if cheat.

## Deploy
Action_05DabneyUS01, _05DabneyUS01, State_highDramaPhase05DabneyUS01.
