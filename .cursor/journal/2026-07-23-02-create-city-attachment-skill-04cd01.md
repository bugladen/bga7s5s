# create-city-attachment skill update from _04cd01

## Why
Penya (`bas/_04cd01`) was the first bas CityAttachment and exercised several shapes the skill barely covered: multi-Action cards, engage-this-card + adjacent City move, **sink to City Deck** (vs destroy / faction-deck sink), Improvising RiskClone ported to AttachmentAction, and first-time expansion JS/State wiring.

## What changed in the skill
- **pattern-c.md** — rewritten with: no AttachmentCityAction + cardInCity gate; multi-Action classes; engage-this-card vs performer; choose-location timing; destroy vs **city sink** (`createCardAddedToCityDeckEvent`); commit-time sink with picker/back rules; full RiskClone subsection (wealth difference vs 01106).
- **wiring.md** — Riposte not required for CityAttachment; sink/destroy/engage memory notes; first-time `bas` State + On*State.bas.js wiring checklist.
- **references.md** — `_04cd01` + `_01106` + `Action_03055`; clarified `_01075` is FactionAttachment.
- **checklist.md** — multi-Action, first-expansion JS, sink vs destroy, commit-time sink, no Riposte.
- **helpers.md** — city deck sink/destroy events, RiskClone createCardInLocation routing, adjacent City includeHome=false.
- **SKILL.md** — bas in anatomy; Title; no Riposte; richer Action shape-table row.

## Intentionally not added
- Separate pattern-h for RiskClone — rare enough to live as a Pattern C subsection with `_04cd01` / `_01106` references.
