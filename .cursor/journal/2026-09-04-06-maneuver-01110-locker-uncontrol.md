# Maneuver_01110 — Locker location fatal on uncontrol choice

## Crash

Tournoi `260903-0151`: `City location Locker-100415874 not found` in `Theah::getCityLocation` via `canLocationBecomeUncontrolledBy` ← `Maneuver_01110::actFromManeuverWithId` id=2.

## Cause

Nothing Personal: wound adversary, then if Combat >= 3 adversary chooses (another wound OR location uncontrolled).

Events resolve wound **before** the choice state. If that wound destroys the adversary, `Location` becomes `Locker-{playerId}`. Option 2 used `$adversary->Location` → not a city location → fatal.

`isAvailableToPlayer` already gates discard/locker at play time; that does not cover death from the maneuver's own first wound.

## Fix

1. **Maneuver_01110**: use `$actor->Location` for "this location" (duel site). Actor is still there after the adversary dies. Same idea as Maneuver_01107 storing `AdversaryLocation` before the wound, but actor remains present so no persisted field. Also skip id=1 extra wound if already in discard/locker.
2. **Theah**: `canLocationBecomeUncontrolledBy` / `canLocationBeClaimedBy` return false when `!locationInCity` instead of calling `getCityLocation` (belt-and-suspenders for any other caller).

## WHY not auto-skip the choice when dead

User: keep the transition so zombie (and human) still reach a passable/choosable state. Purpose of zombie is to get to a state where it can pass — don't short-circuit the transition.

## UI follow-up

When adversary is already in Discard/Locker at choice time: do **not** present "Take Wound".
- `getArgsFromManeuver` → `canTakeWound`
- State `getArgs()` → `argsForState()`
- JS only adds Take Wound button when `args.args.canTakeWound`
- Backend rejects id=1 with UserException if already out of play
- Softened descriptionMyTurn (wound option may be absent)

## Related

- `2026-05-07-02-maneuver-01110-audit.md` — earlier audit assumed `$adversary->Location` fine because duelists share a location; missed post-destruction case.
- `2026-05-19-01-indomitable-will-stuck-game.md` — introduced the canBecomeUncontrolled guards that now throw on bad names.
- Maneuver_01107 — stored location before wound for claim-after-destroy.
