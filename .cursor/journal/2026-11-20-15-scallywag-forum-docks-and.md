# Scallywag Text: Forum AND Docks

## Context
Eddie: `_05Scallywag` Text changed from Forum **or** Docks to Forum **and** Docks. Text property already said "and"; resolve still did `_04045`-style pick.

## WHY
Earlier session kept OR because Text said or (art looked like AND — Anchor+Temple). Journal said flip to Wildcall dual if Eddie confirms AND. He did.

## Change
- `_05Scallywag::handleEvent` queues Forum + Docks Renown (SnowWhite shape). No transition.
- Deleted `State_planningPhaseResolveSchemes05Scallywag`, States const `2605240`, states.inc `"05Scallywag"`, cad.js triple.
- Rum WHY comment: `2605240` freed (keep `2605241`).
- Test: assert dual Renown, zero transitions.
- create-scheme docs: Scallywag is fixed dual; OR pick exemplar stays `_04045` only.

## Tests
`Card_05Scallywag_Test` — 7 pass.

## Unfinished
None for this text flip. Reaction unchanged.
