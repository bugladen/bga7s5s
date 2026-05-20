# Indomitable Will + Reaction_01184 → Game Stuck (and follow-on refactor)

## Original Symptom
A character has `INDOMITABLE_WILL_CONDITION` (set by `Action_01130`, Yevgeni). Another player initiates a basic Claim Action against that location. `Reaction_01184` (Claude de la Roche) triggers on `EventPressureOccuring`. No matter which option the reaction-player picks, the game gets stuck with:

> Indomitable Will: Yevgeni is still at the location. Location cannot be claimed.

## Root Cause
`Action_01130::eventCheck()` only checked `EventLocationClaimed`. In the basic-claim flow:

1. `actHighDramaClaimActionPerformerChosen` (FrameworkActionsTrait.php) creates `EventPressureOccuring` and calls `eventCheck` on it. Action_01130 didn't listen for this event, so nothing threw.
2. State transitions into `stHighDramaPressureLocation`. Pressure resolves. `Reaction_01184` queues, the player picks an option.
3. After the reaction's `nextState("done")`, event processing resumes. The `EventLocationPressureResult` handler in `EventHub.php` creates the `EventLocationClaimed` and called `$theah->eventCheck($claimEvent)` directly — NOT via `queueEvent` which has the try/catch.
4. Action_01130's eventCheck for `EventLocationClaimed` threw `BgaUserException` — but this is during *server-side event processing*, not a player action. The exception escaped up through the state machine, leaving the game half-transitioned.

## Final Design (after iteration with the user)

Rather than encoding the "Indomitable Will blocks claim" rule inside Action_01130's eventCheck (which is card-specific and the wrong layer), we made claimability a property of the CityLocation itself:

### `CityLocation->CanBeClaimed: bool`
New property on the location. Default `true`. Persisted in globals via `Game::getCanBeClaimedForLocation` / `setCanBeClaimedForLocation` (same pattern as `Renown` and `Controller`). Hydrated in `Theah::buildCityLocation`.

### `Theah::canLocationBeClaimedBy(int $playerId, string $location): bool`
Central rule. Just reads the flag off the CityLocation. `$playerId` parameter is reserved for future per-player exemption rules; currently unused but kept on the signature so callers don't have to change later.

### Action_01130 toggles the flag
- On `EventActionTriggered`: queue the claim event first, *then* call `setCanBeClaimedForLocation($location, false)`. Order matters — flip the flag after queueing Yevgeni's own claim so the emit-site guard added to all claim sources (see below) doesn't skip it.
- In `setConditionEnded`: set the flag back to `true`.

### Every claim-emit site now guards with `canLocationBeClaimedBy`
Policy: anywhere `EventFactory::createLocationClaimedEvent` is queued, wrap it in `if ($theah->canLocationBeClaimedBy(...))`. Modified:

| File | Site |
|---|---|
| `FrameworkActionsTrait.php` | `actHighDramaClaimActionPerformerChosen` throws `BgaUserException` upfront (player input handler — clean rejection) |
| `EventHub.php` | EventLocationPressureResult handler (basic-claim emit) — also dropped the now-unnecessary explicit `eventCheck` call that originally caused the stuck-game |
| `Action_01030.php`, `Action_01075.php`, `Action_01095a.php`, `Action_01103a.php`, `Action_01141.php`, `Action_01143.php`, `Action_01206.php`, `Action_01028.php`, `Action_02029.php` | guard around the `queueEvent($claimEvent)` |
| `Maneuver_01107.php`, `Reaction_01080.php` | same guard |
| `Action_01206.php` | also gates in `isAvailableToPlayer` so the button hides when the location can't be claimed (so Captain's Coat doesn't engage / pressure for nothing) |

### Removed
- `Action_01130::eventCheck` for `EventLocationClaimed` / `EventPressureOccuring`. The rule lives in the flag now, checked at emit sites.
- `Action_01130::throwIfLocationCannotBeClaimedBy` (static helper).
- `Theah::getLocationClaimBlocker`.

## Why this design

- **Card-specific rules don't belong in eventCheck throws.** Throwing `BgaUserException` is only safe inside player-input handlers. Anywhere it might run during event processing, the game can stick. Guards at emit sites avoid the issue entirely — they don't emit the event, no throw needed.
- **Adding a new "this location can't be claimed" effect later just toggles the flag.** No new eventCheck across N actions, no new audit of emit sites.
- **The default (`true`) means existing code keeps working** even if someone forgets a guard — they just lose the ability to be blocked for that path, which is the same as today's behavior.

## Files Touched
- `modules/php/theah/CityLocation.php` — `CanBeClaimed` property
- `modules/php/UtilitiesTrait.php` — `getCanBeClaimedForLocation` / `setCanBeClaimedForLocation` / `getCanBeClaimedLocationName`
- `modules/php/theah/Theah.php` — `canLocationBeClaimedBy` replaced; `buildCityLocations` extracted to `buildCityLocation`; removed `getLocationClaimBlocker`
- `modules/php/cards/_7s5s/actions/Action_01130.php` — toggles flag instead of throwing from eventCheck
- `modules/php/FrameworkActionsTrait.php` — basic claim throws upfront
- `modules/php/theah/EventHub.php` — basic-claim emit now guarded by the flag; removed the explicit `eventCheck` that originally caused stuck-game
- All other `createLocationClaimedEvent` emitters listed above

## Follow-up (2026-05-20): Parallel CanBecomeUncontrolled flag

Indomitable Will also prevents the location from becoming uncontrolled (e.g., another player using Action_01086 "Make Location Uncontrolled" against the Indomitable Will'd location should fail). Added a parallel flag using the same pattern:

- `CityLocation->CanBecomeUncontrolled: bool` (default true), persisted via `getCanBecomeUncontrolledForLocation` / `setCanBecomeUncontrolledForLocation`
- `Theah::canLocationBecomeUncontrolledBy(int $playerId, string $location): bool`
- Action_01130 toggles both flags together: false on activation, true on `setConditionEnded`. The reset to true happens BEFORE queueing the (legitimate) uncontrolled event so the new guard at that emit site lets it through.
- All emitters of `createLocationBecomesUncontrolledEvent` now guard with `canLocationBecomeUncontrolledBy` and emit `"${location} cannot become uncontrolled."` when blocked: Action_01086, Action_01112a, Maneuver_01110.
- The stopgap (renamed `backfillIndomitableWillFlags`) now writes both flags from `INDOMITABLE_WILL_CONDITION`.

### Why this pattern instead of handleEvent-cancel
Considered Action_01130 cancelling via `handleEvent` on `EventLocationBecomesUncontrolled` (setting `$event->canceled = true`). Rejected because:
1. `EventLocationBecomesUncontrolled` defaults to `runEventHubAfterCards = false`, so the EventHub uncontrols the location BEFORE Action_01130's handler runs. Flipping that flag would change timing for every other listener (e.g., `_01120` removing its influence bonus) — blast radius beyond Indomitable Will.
2. The emit-site / location-flag pattern is what we already established for `CanBeClaimed`. Keeping the two rules parallel is easier to reason about than mixing two cancellation paradigms in the same card.

## Open Questions / Future Work
- `Game.playerCanBasicClaim` doesn't factor in claimability. A player whose only performers are at blocked locations would still see the "Claim Action" button, then get the `BgaUserException` at performer-chosen. Minor UX leak; acceptable for now.
- `DebugTrait::debug_ClaimLocation` still calls `eventCheck` explicitly before queueing. Harmless (the Indomitable Will check no longer throws from eventCheck), but inconsistent with the new emit-site guard pattern. Left as-is since it's debug-only.
- Other cards' `eventCheck` methods may still throw `BgaUserException` in ways that can surface during event processing. Worth a broader audit using the same logic this fix established.
