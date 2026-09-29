# Overzealous (03022) — Engaged is not a play cost

## Bug
`Maneuver_03022::isAvailableToPlayer` required `count(getValidTargets) > 0` where
valid targets = Engaged characters at the duel location. That greys the Maneuver
unless someone is already Engaged when you play the combat card.

Eddie: Engaged is not a cost. Printed text is `Final Strike • En garde target…`.
Only Final Strike is left of the `•`. En garde is the on-death effect.

## Fix (round 2 — Eddie: getValidTargets still gated Engaged)
Eddie clarified: Engaged must not gate *targets* either. Not just availability.

- Removed `isAvailableToPlayer` override entirely (was parent + `return true` —
  parent Maneuver already handles Fate's Silence blanking)
- `getValidTargets` / `isValidTargetForAbility` = in-play at duel location only
- Pass only when the location is empty of characters
- Resolve: emit `createCardEngardedEvent` only if Engaged (skip notify spam if
  already En Garded); still a legal pick

## WHY this is not like Matushka's Song heal filter
Heal-of-zero is meaningless and Song's heal is optional-tail when empty.
Here the printed effect is "En garde target character" with a mandatory chooser
when anyone is present — Engaged state doesn't restrict who can be the target.
Already En Garded → choose them, no-op the flag flip.
