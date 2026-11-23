# Duel Starting Threat Pool missing lethal skull

## Bug
Lethal shows on Ending Threat Pool for Round N and Round N+1, but not on Starting Threat Pool of Round N+1.

## Why (not a backend carry bug)
`stDuelNewRound` already copies `challenger_threat_is_lethal` / `defender_threat_is_lethal` from the previous row into the new row (same as ending→starting threat numbers). `newDuelRound` notify and `getDuelRows` serialization include the flags. Journal `2026-05-06-01-technique-01049-lethal-carryover-bug.md` documents that carry-forward is intentional.

The miss was pure UI: `displayDuelRow` and `notif_updateRoundThreats` only appended `._7sfs-lethal` to `ending_*_threat` chips. Starting chips got the number + threatened class only.

## Fix
- `Utilities.js` `displayDuelRow`: paint skull on starting + ending when the side flag is set (covers `notif_newDuelRound` and page reload).
- `Notifications.js` `notif_updateRoundThreats`: after rewriting starting chip `innerHTML` with the number, re-append skull if lethal (ThreatModified updates starting_* and would wipe a prior skull).

Did not touch combat/technique/maneuver ending-only notifs — those leave starting alone; new-round path goes through `displayDuelRow`.
