# Leshiye step-2 renown confirm blocked by own remove lock

## Context from user
Day 2, Leshiye + Shifting Tide. Outermost = City Docks chosen. At `planningPhaseResolveSchemes_01126_2`, choosing two other locations for Renown surfaced: "Leshiye of the Wood does not allow Renown to be removed from its location." No renown placement progressing.

Found prior work in `stash@{0}` ("leshiye") with the same diagnosis — not applied to working tree.

## Root cause
`ChosenLocation` is set in step 1 while the scheme is still at Home. `eventCheck` immediately locked add/remove/claim at that location.

Placement discard queued `EventRenownRemovedFromLocation` from `EventSchemeMovedToCity` handleEvent. EventHub sets `Location = ChosenLocation` *before* card handlers run, so the remove lock was armed when Leshiye tried to discard its own site's Renown. `queueEvent` soft-catches and notifies that exact message — looks like a confirm error; discard never lands.

Source/`getInjectCode()` exemption was tried before (b21c0610) and still fought the timing. Don't rely on exemption alone for placement cleanup.

## Fix (kept post-placement rules)
1. Gate all Leshiye renown/claim locks on `Location === ChosenLocation` (and ChosenLocation non-empty). Printed lock starts when Leshiye is on the site, not when step 1 picks it.
2. Keep remove lock + source exemption for after Leshiye is on-site.
3. Queue site Renown discard in `resolveLeshiyeWithRenownLocations` *before* `SchemeMovedToCity` (still Home → locks off).
4. SchemeMovedToCity handler: Id match instead of `==` on embedded scheme copy; no renown discard there.
5. Shifting Tide clear-all: try/catch per location so Leshiye on-site doesn't abort the whole loop.

## Why not stash's direct write / drop remove lock
Stash cleared Renown via direct DB write and removed the remove lock entirely. That leaves "cannot be moved from" unenforced after placement. Event-based discard while still Home + Location-gated locks keeps rules intact.

## Unfinished / watch
- In-flight table at 01126_2 should work after deploy + re-confirm.
- Shifting Tide args still list Leshiye's location for later renown picks — player can select it and get a hard add-block. Pre-existing UX; filter later if annoying.
- `stash@{0}` still has overlapping Leshiye work — drop when comfortable this fix is the keeper.
