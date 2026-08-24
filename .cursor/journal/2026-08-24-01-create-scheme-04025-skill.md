# create-scheme skill update from _04025

## What was new
1. **City Card to fixed + Renown to different location** — Pattern A sibling of `_04014` but fixed step is `createCityCardAddedToLocationEvent` (cast id; guard empty deck) not Renown. Documented in SKILL shape table + pattern-a.md.
2. **Reaction (not Forced) at Planning End** — easy mis-route into Pattern F. Look/Pass via `createReactionTransitionEvent`; follow-on pick still under `PLANNING_PHASE_END_EVENTS` with reaction Id as 4th transition arg. Documented in SKILL, pattern-f contrast, reactions.md section, wiring.md, checklist 4/38/39.
3. **Private look → draw two → sink rest** — Look ≠ Reveal; clamp ≤2 auto-draw; Otto draw pair + faction-deck sink(false). Merchant trait gate + look count = 3 + merchants.

## Files touched
SKILL.md, pattern-a.md, pattern-f.md, reactions.md, references.md, checklist.md, wiring.md
