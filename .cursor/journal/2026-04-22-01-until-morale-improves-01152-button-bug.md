# Until Morale Improves (01152) - Confirm button not disabled in state 2

## The Bug

`PHP Warning: Undefined array key 0` at `_01152.php:95`

In state `planningPhaseResolveSchemes_01152_2` (choose a location to move Renown FROM), the "Confirm Location" button was not initially disabled. Player could click it without selecting a location, sending an empty array to the backend. `$ids[0]` then accesses index 0 of an empty array.

## Root Cause

Missing `dojo.addClass('actCityLocationsSelected', 'disabled');` in `OnUpdateActionButtons.7s5s.js` for the `_01152_2` state. The neighboring states (`_01152` and `_01152_3`) both had this line.

## Fix

Added the missing `dojo.addClass` call to disable the button until a location is selected.

## WHY this pattern exists

The confirm button is always created initially disabled, then gets enabled by `makeCityLocationSelectable` when the player selects enough locations. Without the initial disable, `onCityLocationsSelected` still shows a confirmation dialog ("You did not select as many locations...") but if the player confirms anyway, it submits an empty array. The backend doesn't guard against empty `$ids` because the frontend is supposed to prevent that.
