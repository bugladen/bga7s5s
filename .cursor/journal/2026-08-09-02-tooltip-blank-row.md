# Blank lines in text hover tooltips

Eddie asked if there's a better way than `row('<br>', '<br>')` in `createTextTooltipForAttachment` (also used in Risk).

**Answer:** Yes. Prefer the same full-width pattern already used for ability separators:

```js
const blank = '<tr><td colspan="2"><br></td></tr>';
```

WHY better than `row('<br>', '<br>')`:
- `row` is for label/value pairs; blank spacing isn't a pair
- Matches existing `'<tr><td colspan="2"><hr></td></tr>'` idiom for abilities
- One constant, clearer intent

Applied. Attachment + Risk now use local `blank` constant. Left Character/Scheme/Event alone — they had no spacers.

## Hide RPT on city attachments

Eddie: city deck attachments don't have Riposte/Parry/Thrust — don't show them in hover.

Gate the combat-stat block on `card.deckOrigin === 'Faction'`. WHY: `FactionCardTrait` sets `deckOrigin: 'City'|'Faction'` and is the only place RPT is serialized; city attachments were rendering "-" via `?? '-'` which is misleading noise. Blank before Traits still applies for both.
