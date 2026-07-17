# Action_01130 CanBeClaimed Property Sync

Eddie: Indomitable must check `CityLocation->CanBeClaimed` before succeeding. No Leshiye overlap handling — IW can never be active at a Leshiye location.

## Why

Emit-site guards read `$location->CanBeClaimed`. Action_01130 used to write globals only, so same-request checks after IW activated could still see a stale `true`. Also Leshiye sets `CanBeClaimed = false`, so IW must refuse to start there.

## What stayed / what went

Kept:
1. Availability + trigger read `$location->CanBeClaimed` directly — action fails (and is unavailable) when the location cannot be claimed.
2. `setLocationClaimFlags` writes globals AND the in-memory property so same-request guards see the update.

Removed (Eddie correction):
3. Leshiye-aware restore on IW end. WHY not needed: Indomitable can never be active at a Leshiye location, so ending IW can always restore `CanBeClaimed = true` with no overlap to consider. Dropped the `_01126` import and `locationStillBlocksClaim`.

## Unfinished nearby

`_01126` still has the `EventLocationClaimed` eventCheck exception from the incomplete last-session cleanup.
