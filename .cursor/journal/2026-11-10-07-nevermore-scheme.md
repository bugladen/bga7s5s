# _05Nevermore (The Raven's Designs) scheme finish

## Context
Prior notes: Timebomb is Klim Grabiezca; Nevermore Text was the pasted scheme abilities living on the Timebomb stub until art correction. User asked to finish Nevermore via create-scheme skill; CAD test card → no clienttranslate.

## Classification
1. **Add Renown to Docks** — Pattern A trivial (no GameState). Anchor icon = Docks.
2. **Villain Reaction: At the end of Planning • Target opponent musters Klim from out of play and takes control** — Planning-End Reaction (not Forced). Button opponents + Pass. Mirror Snow White muster/create, but muster playerId = opponent.
3. **Leader City Action: Draw = Artifacts you control** — Pattern H SchemeCityAction (Leader trait gate). No HD sub-state.

## WHY OwnerId = scheme controller on Klim muster
Timebomb flip already documents opponent-controlled Klim: Heart returns via OwnerId, not ControllerId. So createCardInLocation(owner=Nevermore player, controller=opponent) + muster(opponent) leaves OwnerId intact while EventHub sets ControllerId from muster playerId. "Takes control" = ControllerId only.

## Retimed: Dawn → Planning End
Text originally said Dawn End (empty under stock scheme lifecycle). User updated Text to Planning End; switched `EventPhaseDawnEnding` → `EventPhasePlanningEnd`. Home gate now matches Snow White / chosen-scheme lifecycle.

## WHY no GameStates / no JS
Opponent pick and Pass fit reaction buttons (Snow White ≤2 named shape). Leader draw needs no pick after framework performer select (Pattern H / Curry Favor).

## Files
- `_05Nevermore.php` — IHasActions + IHasReactions; resolve Docks Renown
- `reactions/Reaction_05Nevermore.php` — EventPhasePlanningEnd
- `actions/Action_05Nevermore.php` — Leader draw = Artifact count (clamp drawable)
- Skill: SKILL.md shape rows + reactions.md Planning-End Villain section + references.md

## Unfinished / watch
- No regression suite yet (William has one; Snow White / Nevermore don't).
