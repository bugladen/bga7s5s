# Bastien 01063 — "More than one possible transition"

## Context
Eddie hit the error choosing a Musketeer in `duelChooseTechnique_01063`. Immediate follow-on from the Jul 19 back-button work.

## Cause / WHY
When we added `"back" => DUEL_CHOOSE_TECHNIQUE`, the success path stayed as `""` and `Technique_01063Swap` kept calling bare `nextState()`. BGA requires an explicit transition name whenever a state has >1 transition — classic trap already documented on 03042 / 04004_2 / planningPhaseResolveSchemes04004_2.

## Fix
- State: `""` → `"characterChosen"` (+ WHY comment so nobody "simplifies" back to empty)
- Technique: duel path `nextState("characterChosen")`; challenge path still bare `nextState()` (single `""` transition only)

## Opinion
Exactly the bug I should have caught when shipping the back button. Pattern is well-known in this codebase; the empty-string leftover was the landmine.
