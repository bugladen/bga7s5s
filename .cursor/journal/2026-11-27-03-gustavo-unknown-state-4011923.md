# Unknown state 4011923 (Gustavo live-table crash)

## Report
BGA: `Unknown state: 4011923` on table load (`getActivePlayerList` during `setTable`). Trace timestamp GS1 27/11 23:09:44.

## Root cause
4011923 = deleted `HIGH_DRAMA_PLAYER_TURN_01192_3` (Gustavo choose-Risk after multi-ack).

Commit `469564f1` (2026-11-25) collapsed MULTI ack → `_2` bridge → `_3` picker into a single ACTIVE `01192` (log-only reveal). Constants + GameState classes for `_2`/`_3` were removed. A table that was mid-Gustavo on the old `_3` id could no longer resolve the state → hard crash on page load.

WHY this is a deploy hazard: BGA persists numeric state ids in the DB. Removing a registered id without a shim orphans any in-progress table sitting on that id.

## Fix
Keep the live path on `01192` only; restore compat shims:
- `HIGH_DRAMA_PLAYER_TURN_01192_2` / `_3` constants back in States.php
- `State_highDramaPhase01192_3` — same choose-Risk ACTIVE UX as live 01192 (so stuck tables can finish)
- `State_highDramaPhase01192_2` — GAME `stSetCurrentPlayer` then `""` → **live** `01192` (not legacy `_3`)
- `Action_01192` getArgs / pass / withId also accept `_3`
- JS aliases `highDramaPhase01192_3` → `methods['highDramaPhase01192']()` in enter/leave/buttons/EventHandlers

WHY not delete again later: leave shims until no Studio/prod tables can still hold 4011922/4011923 (or accept admin-repairing those tables).

Cleanup tag: grep `@@@STALE_STATE_SHIM_01192@@@` — hits States.php constants, both State_highDramaPhase01192_{2,3}.php files (delete whole files), Action_01192 `|| _3` arms, JS aliases in enter/leave/buttons/EventHandlers, and the two test asserts/case.

## Tests
`Action_01192_Test` + `Card_01192_Test` — 14 pass including compat `_3` pass + constant asserts.

## Same-class risk (not fixed this session)
Otto `01038_2`/`_3` (4010382/4010383) and Kaj `01180_2`/`_3` (4011802/4011803) were removed the same day with the same pattern. Any table mid-Otto/Kaj on those ids will crash the same way. Restore identical shims if reported.
