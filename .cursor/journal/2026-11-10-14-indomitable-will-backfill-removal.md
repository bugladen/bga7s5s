# Remove backfillIndomitableWillFlags

User asked to strip every reference to the Indomitable Will city-load stopgap.

## Why it existed

When Indomitable Will (`Action_01130`) gained `CityLocation->CanBeClaimed` / `CanBecomeUncontrolled` guards, in-flight BGA tables could already have `INDOMITABLE_WILL_CONDITION` stamped without those flags written. `backfillIndomitableWillFlags()` ran on every city build and recomputed both flags from the condition so old games would not stay claimable / controllable-out-from-under.

## Why remove it now

Same class of cleanup as `OLD_CATS_EMBARGO_TARGET` earlier today — upgrade shim no longer wanted. Live tables that somehow still have the condition without flags will not get healed on load; normal play paths that add/remove the condition still set/clear the flags.

## What changed

- Dropped the call and the private method from `Theah.php`
- Left `backfillLeshiyeOfTheWoodFlags` alone (user only named the Indomitable Will backfill)
- Historical journal mentions kept as history

No tests referenced the method name.
