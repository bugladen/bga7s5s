# Early Morning Arrangements — once-per-player City Action

## Bug
Player A uses `_03cd20` City Action, pressure fails, card stays in city.
Action greys out for everyone — Player B cannot try.

## Cause
Central `actHighDramaInPlayActionConfirm` sets `Used=true` on every CardAction.
`Action_03cd20` never cleared it and had no `$playersUsed`. Global Used locks
all players for the Day.

## Clarification from Eddie
Once-per-Day **per player** applies to printed Actions on **city event cards**
(card stays in play). Not a blanket change to every City Action / EventCityAction
base class. Scope the fix to `_03cd20` only, mirroring `_03cd13` / `_01179`.

## Fix
`Action_03cd20`:
- private `$playersUsed`
- gate `isAvailableToPlayer` / `eventCheck`
- on `EventLocationPressureResult` (success **or** fail): append player, notify
  used-list, `$this->setUsed($theah, false)`
- reset on `EventDuskEndOfDay`

WHY mark on pressure result not trigger: attempt is spent when pressure
resolves; fail still consumes the player's once-per-Day without locking others.
WHY setUsed(false) even on success: card leaves for Home, but clearing Used
keeps the per-player list as the only gate and matches the Crabs/Siren pattern.

Also wired used-list UI end-to-end (card delegate, Game::getAllDatas, Templates /
Utilities / Notifications / Setup) like Crabs in a Bucket.

## Tests
`Action_03cd20_Test`: failed pressure locks A only / B still available; success
clears Used + lists player; eventCheck rejects same-day re-use; dusk clears.
