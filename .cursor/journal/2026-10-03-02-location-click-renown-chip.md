# City location click vs renown chip

Prior session: Leshiye step-2 renown lock timing (`2026-10-03-01`). This is a separate UI bug surfaced while testing that flow.

## Bug
At selectable-location states (e.g. `planningPhaseResolveSchemes_01126_2`), clicking the renown chip selected "something", enabled Confirm Locations, then server said "You must choose two locations."

## Why
Renown chips live *inside* the city image:

```html
<div id="dock-image" class="_7sfs-city-image" data-location="...">
  <div id="dock-reknown" class="_7sfs-city-reknown-chip">...</div>
</div>
```

`makeCityLocationSelectable` attaches onclick to the image. Click on chip bubbles up, but `onCityLocationClicked` used `event.target.id` → `"dock-reknown"`. That id went into `selectedCityLocations`, so count matched and Confirm unlocked. On submit, `$(loc).getAttribute('data-location')` on the chip is null → JSON like `[null,null]` → PHP `array_unique` → one entry → count mismatch error.

Same trap for location-control chips and influence lists (also children of the image).

## Fix
`event.currentTarget.id` — the node the handler was bound to (the city image / home endcap), not the overlay child.

Not CSS `pointer-events: none` on chips: tippy tooltips on renown need hover hit-testing.

## Unfinished
None for this bug. Leshiye watch items from 01 still apply.
