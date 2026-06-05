# City view: switched to CSS Grid + subgrid for 4-column alignment

## Task
Eddie was unhappy with cards on the right side of a city location wrapping onto a new line and appearing left-justified — making it look like they were "on the left side" of the location. After two CSS attempts (justify-content, margin-left: auto) that didn't fully solve it, he suggested treating each location like a 3-column table where column 2 (the city-image) lines up across all locations.

## Approach: CSS Grid + subgrid on #city

This is what subgrid was designed for. The pattern:

```
#city                       → display: grid, 4 column tracks (auto auto auto auto)
  └─ _7sfs-city-location    → display: grid, grid-column: 1 / -1, grid-template-columns: subgrid
      ├─ <frontcap>         → col 1
      └─ _7sfs-city-location-contents  → display: contents
          ├─ _7sfs-city-my-cards    → col 2
          ├─ _7sfs-city-image      → col 3
          └─ _7sfs-city-other-cards → col 4
```

Each location row spans the parent's 4 columns. Because subgrid inherits track sizing from `#city`, the widest content per column across all 5 rows determines that column's width — so all city-images line up at the same x-coordinate, all my-cards align on the right side of their column, all other-cards start at the same x.

### Key CSS changes
- `#city`: added `display: grid; grid-template-columns: auto auto auto auto; row-gap: 5px`
- `_7sfs-city-location`: was `inline-flex`, now `display: grid; grid-column: 1 / -1; grid-template-columns: subgrid; align-items: center`
- `_7sfs-city-location-contents`: was `inline-flex`, now `display: contents` (so its children become direct subgrid items)
- `_7sfs-city-my-cards` / `_7sfs-city-other-cards`: changed from `inline-flex` to `flex` (they're now grid cells, not inline)
- Removed `margin-left: auto` from other-cards — no longer needed because grid lays out the columns deterministically
- Hide unrelated empty separator divs via `#city > div:not([id]):not([class]) { display: none }` so they don't consume grid cells

### Why `display: contents` on location-contents
The `_7sfs-city-location` wrapper still needs to be a real grid container (it owns the colored row background, border-radius, margins, position: relative for chip overlays). But `_7sfs-city-location-contents` is just a structural wrapper — making it `display: contents` means its children flow into the location's grid as direct items, occupying cols 2-4. If I'd made location itself `display: contents`, I'd have lost the colored bar background.

### Frontcap vertical centering
Each `<loc>-frontcap` had `margin-top: 30px` (or 33px for forum) — that was a manual offset for the old inline-flex layout to roughly visually center against the image. With `align-items: center` on the grid row, this is automatic; the margin-top would push the frontcap below center. Added `._7sfs-city-location > ._7sfs-city-endcap { margin-top: 0 !important }` — `!important` was needed because the per-location ID selectors (`#oles-inn-frontcap` etc.) outrank a class-based rule.

### alignCityImages → no-op
The JS function added a few days ago that measured the widest my-cards container and set min-width on all of them is now obsolete — grid handles column alignment automatically. Made it a no-op rather than ripping out the ~7 call sites, in case we ever need to fall back.

## WHY this over the previous flex approach
The fundamental problem with `_7sfs-city-location-contents { display: inline-flex; flex-wrap: wrap }`: when the row had to wrap, **the whole `other-cards` container got pushed to a new row** below the image. On that new row, the container sat at the left of the parent (because `inline-flex` sized it to content). `justify-content: flex-end` inside didn't help because the container itself was content-sized — there was no internal slack to justify against. `margin-left: auto` got it onto the right of its own row, but then it still looked like a separate dangling block below the image, which felt messy.

With grid, **there is no wrapping at the parent level** — there are 5 grid rows for 5 locations, each one rigidly laid out into 4 columns. If other-cards has more content than fits, the column expands and the whole row gets wider; if the viewport is too narrow, the page scrolls horizontally rather than reflowing cards into messy positions. That's the right tradeoff: predictable column alignment beats responsive reflow for this kind of game-board UI.

## Browser support note
Subgrid: Chrome 117+ (Sep 2023), Firefox 71+ (Dec 2019), Safari 16+ (Sep 2022). For a BGA game in 2026 this is fine.

## What might still need tweaking
- Mobile: previous instruction was to show "old view" on mobile. The `getTargetElementForLocation` mobile check still routes all cards into other-cards (so my-cards stays empty) which keeps cols 1 and 2 collapsed to roughly the same widths as the old view. But the grid layout itself applies on mobile too — if Eddie wants the old `inline-flex` flow on mobile specifically, we'd need a `@media (max-width: 768px)` block that resets `_7sfs-city-location` and `_7sfs-city-location-contents` back to inline-flex.
- Frontcap forum case: forum-frontcap had `margin-top: 33px` and is 42px tall (vs 51px for the others). The override sets it to 0, then grid centers it. Should look right but worth eyeballing.
