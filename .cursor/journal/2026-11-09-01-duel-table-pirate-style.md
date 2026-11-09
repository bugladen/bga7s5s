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

## Follow-up: duel_stat_value chip
User: round white bg + gold border around `#duel_stat_value`.
Mirrored challenge-stat-chip sizing (28px circle, flex center) so the Combat/Finesse/Influence sprite sits clean on the wood header bar.

## Follow-up: Maneuver/Technique labels off PirataOne
User: revert PirataOne on maneuver/technique labels, then match duel_stats / header th Georgia stack instead of sans-serif. Name cols (6, 8) + label chips; stat cols (7, 9) keep ledger PirataOne for combat chips.

## Follow-up: sunken Maneuver/Technique chips
User asked if sunken look is possible — yes, inset box-shadow (dark top, light bottom) + slightly darker parchment fill. Softened outer border to `#8b6d4b` so the inset reads as the depth cue. Same treatment applied to `._7sfs-threat-table`.

## Follow-up: thrust chip white bar
Yes — CSS sprite window. `boardResources.png` has opaque white through source y≤156 in the thrust X band. Combat chips use `background-size * 0.3` and `-47px` Y → source y≈156.67, so the top of the 30px chip samples that white row (parchment made it obvious; silver used to hide it).

First fix (`-48px`) was wrong: scaled sheet height is 77.1; chip is 30 → max Y offset is 47.1. `-48` overflows the bottom; default `background-repeat` tiled the sheet's *top* white under the swords (worse on parry+thrust). Correct fix: `background-repeat: no-repeat` + Y `-47.1px` (clears white, fits bottom exactly).

Temp sprite crop PNGs under `.cursor/journal/` were debug-only — deleted; math above is enough.

## Follow-up: sunken round indicator
User: same sunken treatment on `._7sfs-duel-round-indicator`, then only the text — not the whole cell.
Moved class from `<td>` onto an inner `<div>` in `jstpl_duel_round`; chip is `inline-block` with padding. Centering needed `display:block; width:max-content; margin:0 auto` + first-col `vertical-align:middle` (text-align alone left it upper-left). Header label shortened to `Round`.

## Follow-up: outer rounded edges
Tried `12px` + `overflow:hidden` + inset brass shadow — user reverted. Back to `6px` radius, outline brass line, no overflow:hidden (gambled rotates).

## Follow-up: center threat pool columns
User: center Starting/Ending Threat Pool cells. Nested `._7sfs-threat-table` gets `margin: 0 auto` + centered td text; outer cols 3 and 10 get text-align/vertical-align middle.

## Follow-up: raised wounds chip
Tried raised look on `._7sfs-duel-wound-chip` — user reverted.

## Follow-up: duel title participants
User: title row text "A duel between X and Y" right of Duel Stat.
- Template: left stats cluster + `#duel_participants`
- `displayDuelTable(stat, challengerName, defenderName)` builds translated string
- `duelStarted` notify + getAllDatas (`duelChallengerName`/`duelDefenderName`) supply names; setup falls back to first duel round
- Participants on the right of the wood banner via inner `._7sfs-duel-stats-row` flex — NOT `display:flex` on the `<td>` (that broke colspan width)

## Font follow-up (same day)
Swapped Georgia/Palatino for `PirataOne-Regular` on `#duel_table` / `._7sfs-threat-table` + the three explicit overrides (header th, `#duel_stats`, round indicator). WHY inherit on table: one place owns the ledger face; overrides kept so they don't fall back if something resets font-family. Font file already in `fonts/`.
