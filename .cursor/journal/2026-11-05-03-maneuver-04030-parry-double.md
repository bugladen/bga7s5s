# Maneuver_04030 — does Parry apply twice?

User asked whether Tip the Scales Maneuver double-applies its Parry bonus.

## Verdict

No. `EventDuelCalculateManeuverValues` does `$event->parry += 1` once when `$ChooseParry`. Regression asserts `parry == 1`. Pattern C.3 mirror of `Maneuver_03024`.

## Why it looked like twice

Choice act notified "adds 1 Parry", then calc explanation notified the same string via EventHub. Two chat lines; one DB write. Printed Parry 2 + maneuver +1 = 3 is correct.

## Fix

Choice act now announces "${player} has chosen +1 Parry/Thrust" instead of "adds 1 Parry/Thrust". Calc explanations still announce the actual add. User preferred a choice line over silence.
