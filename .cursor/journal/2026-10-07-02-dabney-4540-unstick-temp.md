# Dabney orphan transition — TEMP unstick at 4540

## Situation
Player stuck at state 4540 (`HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS`)
with GS1: transition `05DabneyUS01_2` impossible.

Same orphan as 2026-10-04-06. Permanent fix already in `stIssueChallenge` (+ Action
emit cleanup). That only helps tables that still pass through issue-challenge.
This table is already *inside* the SETUP events hub with the leftover row still
in `events`, so every `stRunEvents` retry still tries the bad transition.

## Approach
TEMP purge at the top of `stRunEvents` via existing
`deleteQueuedTransitionsNamed("05DabneyUS01_2")`.

WHY here (not another one-off state action / Studio SQL):
- 4540's (and 4530's) `action` is already `stRunEvents` — after deploy, re-running
  the stuck state is enough; no manual DB edit per table.
- Helper already exists and matches serialize form correctly.
- Delete is a no-op when clean — safe on every hub until we remove the TEMP.
- Easy to rip out: one comment block + one call.

WHY not permanent in stRunEvents: prevention belongs at the emit/issue chokepoint
(`stIssueChallenge`). Keeping a forever DELETE on every event hub for one card's
legacy orphan is noise; remove once stuck tables have moved on.

## After deploy
Re-trigger the game state's action (Studio reload / re-run of the failed game
state). Hub should drain remaining legit events → `endOfEvents` → resolve
technique path.

## Remove later
Search `TEMP_REMOVE_AFTER_DEPLOY` (or `dabney-05DabneyUS01_2-4540-unstick`) in
`StatesTrait::stRunEvents`. Delete the marked block once no live tables are stuck
on 4530/4540 from this orphan. Keep `stIssueChallenge` purge — that stays.
