# Duel table collapse to top N rows

## Ask

Only show the top rows of the duel table; text prompt at the bottom expands the rest. Started as top 3 → 6 → settled on top 5. Also restyle the expand control like the duel ledger.

## WHY top-N works with insertion order

`displayDuelRow` places each new round with `dojo.place(..., headerRow, 'after')`, so order is always:

1. stats banner
2. header
3+ newest rounds first (older pushed down)

Collapsed CSS `#duel_table._7sfs-duel-collapsed > tbody > tr:nth-child(n+6) { display: none }` keeps the top 5 (stats + header + 3 newest rounds) and hides history. JS prompt threshold is `rowCount <= 5`. No need to track "current round" in JS.

## Expand bar chrome

Matched wood header bar (`#6b4f34`→`#4a3420`), PirataOne, brass top seam + outline, cream text/shadow — same language as `#duel_table` header rows. Wrapper gets `_7sfs-duel-expand-visible` so the table drops bottom radius/border and the bar sits flush as the ledger footer (avoids fragile `:has([style…])`).

## Implementation

- Template: table starts with `_7sfs-duel-collapsed`; `#duel_expand_prompt` sibling under `#duel` (inside `#duel_wrapper`)
- `setupDuelExpandPrompt` / `updateDuelExpandPrompt` in Utilities — toggle class, hide prompt when `rows.length <= 3`
- Copy uses `_('Show earlier rounds')` / `_('Hide earlier rounds')`; carets appended outside `_()` so translators don't fight glyphs
- Prompt styled as italic underlined parchment text (not a button/card) to match ledger chrome

## WHY not max-height + overflow

Round rows are tall and uneven (actor cards, threat nested tables). A pixel height would clip mid-row. Hiding whole `<tr>`s is clean and keeps horizontal scroll sync / gambled-card rotate clipping concerns unchanged (still no overflow:hidden on `#duel_table`).

## Unfinished / QA

Live duel with 2+ rounds: confirm newest stays visible, prompt appears after round 2, expand/collapse, mid-duel refresh still collapsed by default.
