# Bloody Entrance 03032 — Pass on Extra Turn

## Bug

User: Action_03032 grants another turn but Pass is missing from that turn.

## WHY it was wrong

Original Pattern A.2 (2026-07-06) read the italic *(It must be performed and they must be the performer of the action)* as "mandatory — hide Pass." That was over-reading. Card body says **"may** perform another action." The italic only locks *who* must be the performer if they take the follow-up, not *whether* they must act.

Also softlocks: after wound+move the locked character can have zero legal actions → stuck forever without Pass.

## Fix

Keep `EXTRA_ACTION_PERFORMER` lock (performer choosers / `can*` filtering). Re-allow Pass:

1. `actHighDramaPass` — removed `mustPerformExtraAction()` throw
2. `OnUpdateActionButtons.js` — always show Pass on `highDramaPlayerTurn`
3. `argPlayerTurn` — dropped unused `mustPerformAction` flag
4. Notify copy: "must" → "may"
5. Skill Pattern A.2 / helpers / checklist / references — Pass allowed

## Timing note (why Pass works cleanly)

Bloody Entrance sets `EXTRA_ACTIONS=1` then `ActionResolved` → `stNextPlayer` consumes EXTRA_ACTIONS (1→0) and **stays** on same player with `EXTRA_ACTION_PERFORMER` still set. So on the extra turn EXTRA_ACTIONS is already 0. Passing → `stNextPlayer` else-branch clears the performer lock and advances. No need to specially clear EXTRA_ACTIONS on Pass.
