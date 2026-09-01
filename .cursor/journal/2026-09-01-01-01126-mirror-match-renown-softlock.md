# 01126 Mirror-Match Renown Soft-Lock

Eddie reported 2p mirror with two Leshiye schemes: P1 Docks (renown Forum+Bazaar), P2 Bazaar → can't place renown on Docks, exception in eventCheck but no valid alternative.

## Root Cause

In 2p, `getOuterCityLocations()` is Docks + Bazaar only. Card text "add Renown to two **other** locations" means the non-chosen city locations — always exactly Forum + the other outer. Not a free pick of any two.

When P1's Leshiye is on Docks, `eventCheck` on all cards blocks `EventRenownAddedToLocation` at Docks. P2 choosing Bazaar *requires* renown on Docks and Forum. Docks fails → only one valid target → step 2 confirm throws UserException.

Step 1 (`PLANNING_PHASE_RESOLVE_SCHEMES_01126`) only checks `getOuterCityLocations()`, not whether the mandatory renown destinations pass eventCheck. UI shows both outer locations as valid.

Not a hard game lock: step 2 has `actBack` to re-pick. P2's only valid outer choice after P1 took Docks is also Docks (renown → Forum + Bazaar). But Bazaar should never have been offered.

## Fix Implemented (2026-09-01, revised)

Removed 2p auto-renown and scheme-renown Leshiye exemption. Instead:

1. **Step 1**: All outer locations (`getOuterCityLocations()`) — never filtered by another Leshiye in play.
2. **Step 2** (`locationIds`): Only renown targets that pass `eventCheck` and are not occupied by an active Leshiye.
3. **Strict `eventCheck`**: No bypass for scheme resolution; renown cannot be placed on a Leshiye location.

Mirror match after P1 on Docks: P2 step 1 offers both Docks and Bazaar. Step 2 after choosing Bazaar shows only Forum (Docks hidden — Leshiye there).

## Fix Implemented (2026-09-01) — superseded

Auto-renown + schemeRenownPlacement exemption removed per user feedback.

## Original Fix Direction (superseded)

1. **Validate at step 1** (best): Before accepting chosen location, compute non-chosen city locations and eventCheck renown on each. Require ≥2 pass (3p+) or all pass (2p where count is exactly 2). Reject with clear message naming blocked location(s).

2. **Filter UI at step 1**: `argsFromCard` returns `validOuterLocations` so client only selects viable options. Mirror `_02045` step-2 validation (count, uniqueness, != chosen) on `_01126` too.

3. **2p auto-skip step 2**: Renown targets are deterministic — auto-queue and skip state 2. UX improvement, doesn't replace step-1 validation.

4. **Do NOT exempt scheme-resolution renown from opponent Leshiye block** — card text is clear; the bug is offering impossible choices upstream.

`_02045` has same two-state flow but no ongoing renown block on its own card; opponent Leshiye still creates the same trap without step-1 validation.
