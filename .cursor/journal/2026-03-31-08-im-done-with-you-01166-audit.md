# I'm Done With You (_01166) Audit

## Card Text
> Maneuver: +1 [Parry] for each other card in your dueling line.

## Bug Found & Fixed

**The maneuver counted itself in the dueling line total.** The card text says "each **other** card" but the code did `getCardObjectsAtLocation(LOCATION_DUELING_LINE, controllerId)` and used `count($cards)` directly — which includes the _01166 card itself. This gave +1 extra Parry every time the maneuver was used.

**Fix**: Added `unset($cards[$owner->Id])` before counting. The `getCardObjectsAtLocation` returns an array keyed by card ID (cast to int from DB), and `$owner->Id` is also int, so the unset correctly removes the owning card.

Also fixed the `Maneuver::$Name` and explanation string to include the word "other" to match the card text.

## Things That Are Correct

- Base card stats (Parry 1, dashed Riposte/Thrust, WealthCost 0) look right
- Traits (Flourish, Demoralize) correctly set
- The maneuver correctly gates on `EventDuelCalculateManeuverValues` with matching `$event->maneuverId == $this->Id`
- Calls `parent::handleEvent($event)` which handles `EventManeuverActivated` use-tracking and duel-end reset
