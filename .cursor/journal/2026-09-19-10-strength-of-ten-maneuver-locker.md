# Strength of Ten (01139) — Maneuver did not send to Locker

## Report
Eddie: When Strength of Ten's Maneuver is used, it should send itself to the Locker. It went to discard at the end of the duel.

## Root cause
`Maneuver_01139` sets `$owner->goToLocker = true` on `EventDuelCalculateManeuverValues`. `_01139` listened for locker send on `EventDuelCalculateCombatCardStats` — that event fires when the combat card is *played*, before the Maneuver is chosen. Flag was always still false → no locker event → card stayed on Dueling Line → `stDuelEnd` discards everything on the line with `AsPlayed=false` (so the Action-path discard→locker handler also missed it).

Action path was fine: `AsPlayed` discard after `EventActionTriggered` sets the flag.

## Fix
Replace the dead `EventDuelCalculateCombatCardStats` handler with `EventDuelEndOfRound` (+ `EventDuelEnd` fallback), same deferral discipline as `Technique_02043a`. WHY not immediate on CalcManeuverValues: keep card in `Theah::$cards` for the rest of the round; leave the line before duel-end discard sweep so it never lands in discard.

## Not changed
`Maneuver_01139` still only sets the flag (Miyato clone's owning card is the Character, so `instanceof _01139` skips — clone won't flag Miyato). Action discard+AsPlayed path unchanged.
