# Dark Gift home location selectable marker

## Bug
Dark Gift Action_01046a (wound + move adjacent) — Home destination not showing the selectable image/marker on the home endcap.

## Cause
PHP: `getAdjacentCityLocations($attachedTo->Location)` defaults `includeHome=true`, so Home is in `locationIds` whenever the performer is in a city site.

JS `highDramaPhase01046a` only called `makeCityLocationSelectable` on every locationId. That adds `_7sfs-selectable` but not `_7sfs-home-endcap-marker` (the boardResources sprite that makes Home look clickable). Pattern already used correctly by basic Move and `highDramaPhase01060`.

## Fix
`OnEnteringState.7s5s.js` `highDramaPhase01046a`: branch on `LOCATION_PLAYER_HOME` → `makeHomeEndcapMarkerSelectable()`, else city selectable. Leave path already `resetCityLocations()` which strips the endcap marker class.

## WHY not change PHP
Home being adjacent is correct rules for this action. Only the client marker path was wrong.

## Audit (same session): other Home picker gaps
Cross-checked every Home-offering map picker vs `makeHomeEndcapMarkerSelectable`.

Already OK: Move, 01046a (fixed), 01060 source, 01093, 01133_2, 03055, 03029_3, 05Refuge.

Still GAP (then fixed same session on ask):
1. `highDramaPhase01060_3` — Action_01060 dest step; `getAdjacentCityLocations` default includes Home; JS city-only → endcap branch added.
2. `highDramaPhase03032` — Action_03032 explicitly appends Home; JS city-only → endcap branch added.

Leave handlers already call `resetCityLocations()` for both.

Defensive Home branches without PHP Home (02015/02035/02023_2/02025_2) not gaps.
