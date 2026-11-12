# City chrome → Stone grey

## Ask
User: update the color of the city to Stone grey.

## What changed
City framing was hardcoded `saddlebrown` across `#city`, discard/locker/day chips, SVG tower stroke, Halloween lantern borders.

Introduced `--_7sfs-city-color: #9F9484` on `:root` (named Stone Grey hex) and pointed every former `saddlebrown` city-chrome rule at `var(--_7sfs-city-color)`.

## WHY CSS variable
Seventeen call sites; next palette tweak should be one edit. Per-location row colors (`#city-oles-inn` etc.) untouched — those are location hues, not city chrome.

## WHY #9F9484
Common "Stone Grey" named hex (warm mid grey). Not Tailwind stone-500 (`#78716c`) — that reads darker/colder than the paint name the user used.

## Not changed
Home-row / faction colors, location strip backgrounds, Halloween body overlay.

## Follow-up: location borders
User: more distinction between city and locations → `1px solid black` on `._7sfs-city-location` (matches `#city` outer border). `box-sizing` already border-box so grid sizing stays stable.

## Follow-up: day-phase border
User: border around `#city-day-phase`, omit bottom. Replaced same-color `3px` chrome border with `1px solid black` + `border-bottom: none` so it tabs into the city top edge.

Same treatment on `#day-indicator`, `#city-discard`, `#city-locker` (were also `3px` city-color).

## Follow-up: chip padding
User: few px pad on L/R/top of discard, locker, day.

First try: single `background-clip: content-box` — map blue in pad.
Second try: `clip: content-box, border-box` with only `background-color` — still blue; color follows the bottom image layer's clip, second value is ignored with one image.
Fix: two `background-image` layers — sprite + `linear-gradient(city, city)` with `clip: content-box, border-box`. Halloween city discard/locker keep the gradient layer; home stays transparent single PNG.

## Follow-up: bottom city wall
User: couple px padding on bottom city walls → `#city` `padding: 15px 15px 17px` (+2 bottom).
Clarified: on the city chrome, not a location row (locations vary by player count; city pad covers whichever is last). Reverted once by mistake thinking they meant location; re-applied on `#city`.
