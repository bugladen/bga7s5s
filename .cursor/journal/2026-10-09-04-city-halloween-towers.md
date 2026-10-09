# City Halloween towers (CITY_HALLOWEEN)

## Context
User asked to replace the four city corner towers with lit jack-o-lanterns, then to gate that behind a `CITY_HALLOWEEN` flag (show when `1`).

## What / Why
- Towers stay as `#city-ul-tower` etc. in Templates.js (SVG rects). **Do not remove those ids** — Notifications FLIP city-deck cards from `#city-ul-tower`.
- Flag is **client-only** `window.CITY_HALLOWEEN` in `seventhseacityoffivesails.js` (same pattern as `isDebug`). WHY not a game option / gamedatas: seasonal art only; no server state; flip + redeploy when Halloween ends.
- Setup.js: if flag === 1, `dojo.addClass('city', '_7sfs-city-halloween')`.
- CSS under that class: hide SVGs, 30×30 `background-image: url('img/jackolantern.jpg')`, drop black borders.
- Asset: generated `img/jackolantern.jpg` (black bg, lit pumpkin). Same file on all four corners.

## Toggle
```js
window.CITY_HALLOWEEN = 1; // on
window.CITY_HALLOWEEN = 0; // normal SVG towers
```
Currently set to **1**.

## Unfinished / watch
- At 30×30 the carved face may be muddy — if Eddie complains, regenerate a simpler flatter icon or crop tighter.
- Black square behind pumpkin may look odd on non-black board edges; could chase a transparent PNG later.

## Follow-up: borders
User: jackolantern read as plain black block. Restored city chrome — `3px solid saddlebrown` + `box-sizing: border-box` + `background-size: cover` (was `border: none` / fixed 30px size which hid the frame that made original SVG towers read as towers).

## Follow-up: headless horseman
Asset `img/headlesshorseman.jpg`. Wrapped `#city-day-phase` in `#city-day-phase-row` (flex) with `#city-halloween-horseman` sibling so it sits immediately right of the phase chip as text width changes. Horseman only `display:block` under `._7sfs-city-halloween`. Setup + dawnBeginning now show the **row** (`display:flex`), not `#city-day-phase` alone. Tooltip: "Happy Halloween!"

## Follow-up: horseman legibility
First horseman was dark gothic linework — mud at 31px. Regenerated as flat bold icon: big bright pumpkin as focal point, simple black horse/cloak silhouettes, thick shapes, no fine detail. Overwrote `img/headlesshorseman.jpg` (and assets copy). CSS size unchanged.

Still unreadable (black-on-black). Regenerated again with light cream parchment bg (#F5E6C8) so dark silhouette pops; CSS `background-color` matched.

## Follow-up: holiday folder
Moved both assets to `img/holiday/` (`jackolantern.jpg`, `headlesshorseman.jpg`). CSS urls updated to `img/holiday/...`.

## Follow-up: discard spiders
**Mistake:** first spider art was the cream fan cards = `_7sfs-hand-count` (`-315px` @ 65% → src ~485), NOT discard. Renamed to `img/holiday/hand-count.jpg`.

**Real discard:** `_7sfs-home-discard` / `#city-discard` share `-208px` @ 50% → src (416,0,72,48) — dark stacked-card silhouette (black+alpha on transparent; reads on saddlebrown). Holiday: `img/holiday/home-discard.jpg` with 2 bright orange spiders (black wouldn't read on dark stack). Kept `home-discard-base.png` + `hand-count-base.png`.

**CSS/JS:** Setup also `dojo.addClass(document.body, '_7sfs-halloween')` — hand-count and home-discard live outside `#city`. Rules under `._7sfs-halloween` for both; city-discard uses home-discard.jpg.

## Follow-up: hand-count transparency
AI JPG had opaque black bg (JPG can't alpha). Rebuilt `hand-count.png` from transparent `hand-count-base.png` crop + GDI+ dark-purple spiders. CSS → png, `contain`, `background-color: transparent`.

## Follow-up: hand-count spider size
User snip showed purple specks under the "7". Thin spiders die at 35×32. Redrew as chunky dark-purple silhouettes (thick body + short fat legs), scale ~10 on 8x work — ~30–40% of a card face. Center left clear for numeral.

## Follow-up: home-discard transparency
`home-discard.jpg` had baked saddlebrown — wrong on faction-colored home rows. Rebuilt `home-discard.png` from alpha sheet crop + orange spiders (readable on dark stack). CSS → png, contain, transparent. Deleted jpg. Same asset for `#city-discard` and `._7sfs-home-discard`.

## Follow-up: home-discard spider size
Spiders too small after transparent rebuild. Bumped chunky orange spiders to scale ~12 on 8x work (~15% of 36×24 chip is orange). Output 72×48 PNG.

## Follow-up: city-discard bg
Halloween rule set `background-color: transparent` on BOTH home + city discard. City chip lost saddlebrown (user snip). Split: home stays transparent; `#city-discard` restores `saddlebrown`.

## Follow-up: discard spider colors
Size was good; legs were orange too. Redrew same scale with black legs, orange bodies (legs drawn under body).

## Follow-up: discard spider angles
User wanted more realistic crawl pose. Same size/colors; RotateTransform −35° and +50° so they aren't upright stamps.

## Follow-up: hand-count spiders match discard
Restyled `hand-count.png` spiders same as discard: orange bodies, black legs, crawl angles −65° / +55°, elongated abdomen via polygon. Still transparent PNG; parked on side cards for numeral.

## Follow-up: jackolantern fill
User: too much black padding in `img/holiday/jackolantern.jpg`. Kept 1024×1024; ImageMagick `-fuzz 5% -trim` then `-resize 980x980` centered on black `-extent 1024x1024`. Content was ~746×741 (+146+116 margins) → ~980×973 (+22+25). WHY 980 not full bleed: tiny margin so stem/edges don't clip at 30×30 `cover` crop.

## Follow-up: horseman gloomy grey bg
User: cream parchment → gloomy grey, but not dark enough to mud black silhouette at 31px. Flood-filled cream from edges → `#94989F` (148,152,159); remapped cream/black AA edges; left pumpkin + orange glow alone. CSS `#city-halloween-horseman` `background-color` matched (was `#F5E6C8`). WHY mid cool grey not charcoal: earlier dark gothic art was illegible at chip size — need contrast vs solid black horse/cloak.

## Follow-up: horseman no gold border
User: remove gold border from `#city-halloween-horseman` → `border: none`.

## Follow-up: horseman transparent PNG
User: transparent PNG + remove CSS border. Flood-removed gloomy grey `#94989F` → alpha; silhouette AA as soft black alpha (no grey halo); pumpkin/glow kept. Saved `img/holiday/headlesshorseman.png`, deleted `.jpg`. CSS: `url(...png)`, `contain`, `background-color: transparent`, `border: none`. WHY transparent: grey chip square looked framed; PNG floats silhouette+pumpkin on the board like hand-count/home-discard holiday overlays.

## Follow-up: home-locker skull
User: replace `_7sfs-home-locker` sprite when Halloween with white skull facing 45° left, transparent PNG, legible at 25×23.

**Asset:** `img/holiday/home-locker.png` — AI gen with green chroma bg, then GDI+ pixel pass (G-dominant → alpha) because IM `-transparent`/`floodfill` failed on non-pure #00FF00 JPEG greens. Solid dark eye/nose/teeth (not hollow cutouts) so face still reads on faction-colored home rows.

**CSS:** `._7sfs-halloween ._7sfs-home-locker` + `#city-locker` → contain/center/transparent PNG. Home transparent (faction colors); city keeps saddlebrown — same split as discard. WHY also city-locker: same boardResources offset sprite as home; discard pattern already swaps both.

**WHY not rotate in CSS:** art is already three-quarter-left; CSS rotate would also tilt the face and look wrong at chip size.

## Follow-up: locker skull dead space
User: lots of dead space around skull. First pass only nudged scale inside square 878² — barely helped because `contain` sizes the whole square. Real fix: strip residual mint-green chroma fringe (opaque AA leftovers inflated bbox), then **tight crop to content** (~674×830, ~1.5% margin) — no re-pad to square. Now fills 100% of chip height at 25×23; side gap is skull aspect (~74% width). If Eddie wants full bleed on the chip, switch CSS to `cover` (will clip a bit).

## Follow-up: home-panache witch hat
User: replace `_7sfs-home-panache` sprite when Halloween with witch's hat, transparent PNG.

**Asset:** `img/holiday/home-panache.png` (~821×850). AI gen came back as opaque cream/gray JPG; GDI+ pass keyed near-white neutrals → alpha, soft mid-gray AA → black alpha, then tight crop (~1.5% margin). Kept `panache-base.png` (40×25 extract from boardResources at -313,0) for reference.

**CSS:** `._7sfs-halloween ._7sfs-home-panache` + `._7sfs-score-panache` — contain/center/transparent. WHY both: user wants score-row chip swapped too (same hat asset). Black hat body keeps white panache number readable.

**Spacing:** Halloween-only `margin-left: -10px` on home panache (Eddie tuned from -5); score chip left alone.
