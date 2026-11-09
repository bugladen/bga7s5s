# Duel table pirate/parchment restyle

## Context
User: duel table looks like a 90s HTML table; wants more stylish / piratey.

## Approach
CSS-only. Keep `#duel_table` structure, IDs, column nth-child rules, and threat-table collapse/separate split — JS and acting-character gold border depend on those.

Visual direction: match existing game UI parchment/wood (deck picker `#e8d5b7`/`#6b4f34`/`#3b2a1a`, confirm dialog `#d4b896`), not invent a new palette. Ledger / ship's log feel:
- parchment body instead of silver
- dark wood outer border + soft shadow
- stained header row
- brass/gold accents (already used for acting character)
- Georgia/Palatino system serif for headers only (BGA forbids `@import`)

## WHY not rebuild as div grid
Hundreds of JS selectors target `duel_round_*` cells and nested threat tables. Restyle is the safe win.

## Watch
`#duel_table, ._7sfs-threat-table` shared silver bg — split so nested threat panels get a quieter inset parchment, not the full ledger chrome.

No `overflow:hidden` on `#duel_table` — gambled cards get `._7sfs-engaged` rotate and would clip. Nested `._7sfs-threat-table` keeps overflow hidden for its own radius.

## Done
CSS restyle shipped in `seventhseacityoffivesails.css`. Awaiting user eyeball in a live duel.
