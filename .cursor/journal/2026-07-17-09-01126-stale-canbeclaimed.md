# _01126 In-Memory CanBeClaimed Stale After Globals Update

Eddie asked whether `_01126`'s in-memory `CanBeClaimed` can go stale after updating globals.

## Answer: Yes

`_01126` only calls `setCanBeClaimedForLocation(...)` (globals) on move-to-city and locker. It does **not** set `$location->CanBeClaimed`.

Readers (`canLocationBeClaimedBy`, emit-site guards) use the in-memory `CityLocation` property. That property is hydrated from globals only in `buildCityLocation` (once per Theah build). So within the same request after Leshiye flips the global, in-memory stays at the old value until the next rebuild.

This is exactly the bug class `Action_01130::setLocationClaimFlags` fixed — that helper writes globals AND `$location->CanBeClaimed`. `DebugTrait::debug_SetLocationCanBeClaimed` also syncs both.

## Fix shape (if Eddie wants it)

Mirror 01130: after `setCanBeClaimedForLocation`, also:

```php
$event->theah->getCityLocation($this->ChosenLocation)->CanBeClaimed = false; // or true on locker
```

Both sites in `_01126` (EventSchemeMovedToCity / EventCardSentToLocker).

Eddie approved this fix. I will update both lifecycle sites directly rather than introduce
a helper: there are only two writes, and keeping the persisted and in-memory assignments
adjacent makes the synchronization requirement visible at each transition.

## Result

Updated both lifecycle sites to synchronize `CityLocation->CanBeClaimed` immediately after
writing the global. This prevents same-request readers from seeing the pre-transition value;
the next request's normal hydration remains consistent with the same persisted value.

PHP lint, IDE diagnostics, and `git diff --check` passed. Updated the existing related result
file at `_results/2026-07-17-01126-claim-guard.md`.

## Related unfinished

`_01126` still has the `EventLocationClaimed` eventCheck exception — journal 04 said it was removed for the flag pattern, but current file still has it. Separate from the stale-property question.

## Follow-up question: helper?

Eddie asked whether the dual-write should move into a helper.

Recommendation: **yes, but not into Action_01130's private `setLocationClaimFlags`.**
That helper also toggles `CanBecomeUncontrolled` (IW-only). Leshiye only cares about claim.

Better home: a shared Theah writer that always does globals + in-memory, e.g.
`Theah::setLocationCanBeClaimed($name, $bool)`. WHY: CityLocation lives on Theah; readers
already go through Theah; one place prevents the next caller from forgetting. Action_01130's
private helper could then call that (plus the uncontrolled twin).

Do **not** only enhance `Game::setCanBeClaimedForLocation` without care — that method is
persistence-only today (like Renown/Controller globals setters). Enhancing it is viable if
it conditionally syncs `$this->theah` when the city is built, but a Theah-owned API is clearer.

## Helper migration (Eddie approved)

Added `Theah::setLocationCanBeClaimed` and `Theah::setLocationCanBecomeUncontrolled`.
Migrated:
- `_01126` lifecycle (locker + move-to-city)
- `Action_01130::setLocationClaimFlags` (now thin wrapper over both helpers)
- `DebugTrait` debug setters
- both Theah backfills

WHY keep Game globals setters: hydration (`buildCityLocation`) still needs a persistence-only read/write path; putting dual-write only on Theah keeps that split explicit.

Wrote `_results/2026-07-17-theah-canbeclaimed-helpers.md`. PHP lint / IDE / whitespace clean.
