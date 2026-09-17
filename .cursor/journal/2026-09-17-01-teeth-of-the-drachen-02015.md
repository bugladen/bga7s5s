# Teeth of the Drachen (02015) — fewer than two empty locations

User reported: "Stand Your Ground (02015)" blocks when only 1 location has no Renown. Card 02015 is actually **Teeth of the Drachen** (same effect text). Name mix-up — effect matches.

## Bug

Resolve always transitioned to a pick state and hard-required `count($ids) == 2` + JS `numberOfCityLocationsSelectable = 2`. With only one empty city location, Confirm never enabled / server rejected — player stuck.

## Fix

Mirrored **Castillian Caper (02035)** which has nearly identical printed text ("two different locations with no Renown") and already did do-as-much-as-possible:

- 0 empty → notify, no transition
- 1 empty → `requiredLocationCount = 1`
- 2+ → require 2

Changed: `_02015.php` (helper + args + act validation), state description copy, `OnEnteringState.tac.js` to use server `locationIds` / `requiredLocationCount`.

## WHY this approach

Don't invent a new UX — 02035 is the established pattern for this exact scheme clause. Keeping both cards aligned avoids future "why does one allow 1 and the other doesn't" drift.

Intervention-at-uncontrolled-locations clause untouched.
