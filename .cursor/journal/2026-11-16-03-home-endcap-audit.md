# Home endcap location-picker audit

## Task
Find every PHP path that can put LOCATION_PLAYER_HOME into client location-picker args, then check OnEnteringState for makeHomeEndcapMarkerSelectable.

## Method
1. getAdjacentCityLocations default includeHome=true → who feeds locationIds/locations
2. Explicit append of LOCATION_PLAYER_HOME into destination lists
3. Cross-check JS handlers

## GAPs found
1. **01060_3** — Action_01060 getArgs for HIGH_DRAMA_PLAYER_TURN_01060_3 uses getAdjacentCityLocations($location) with default includeHome. When from city, Home is in locationIds. JS highDramaPhase01060_3 only makeCityLocationSelectable. (highDramaPhase01060 source picker already has endcap branch.)
2. **03032** — Action_03032 getValidDestinationLocations appends Home. JS highDramaPhase03032 city-only.

## OK (Home offered + endcap branch)
- Move: highDramaMoveActionChooseLocation (ArgumentsTrait getAdjacent default)
- 01046a, 01060 (source), 01093 (non-first), 01133_2, 03055, 03029_3, 05Refuge

## Not map pickers / do not offer Home in args
- 02015/02035: cityLocationNamesWithNoRenown only — JS branch is defensive
- 02023_2/02025_2: includeHome=false — JS branch defensive
- Reaction_01039: offers Home via reaction buttons (getAdjacent default), not city location picker / endcap
- 01115/03011/01062/02058: Home in adjacency used for character targeting, not locationIds
- Forced move-home actions: no picker

## WHY this matters
makeCityLocationSelectable on "Player Home" does not add _7sfs-home-endcap-marker (boardResources sprite). Same bug class as Dark Gift 01046a fix today.
