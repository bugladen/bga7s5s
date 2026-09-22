# Through Thick and Thin (_04015) implementation

## Orientation
Prior: Forged for Battle (_04014) Continuous Reaction + fixed Docks Renown. This is next BAS scheme scaffold.

## Text clauses
1. **Resolve:** Add a Renown to two different locations. → Pattern A free two-pick (`actCityLocationsForReknownSelected`), mirror `_03006`.
2. **Action:** Target an uncontrolled City location • Move your Kaspar Dietrich and your Daniella Dietrich there and they each heal a wound. Then you may discard an available City Card from that location. (Complete as much as possible.)

## Pattern / design decisions
- Keyword is **Action:** not City Action → `SchemeAction` (not `SchemeCityAction`). No performer. Default `RequiresPerformerSelected = false`.
- Kaspar/Daniella: multiple cards share those Names (`_01035`/`_03014` Kaspar; `_01036`/`_03013` Daniella). Match by `$character->Name === clienttranslate('…')`, not CardNumber. WHY: Eddie explicitly called out multiple cards with those names — id hardcoding would miss the FAF reprints.
- "Complete as much as possible": move/heal whichever Dietrichs the player controls in play; skip missing ones. Availability still requires ≥1 Dietrich in play + ≥1 uncontrolled city location so the action isn't a discard-only noop.
- Heal only if `Wounds > 0` (same as `Action_02040`). Skip `createCardMovingEvent` when already at the target location.
- Optional discard = HD state 2 with Pass (`actFromActionPass`). Skip state 2 when no discardable available city card at the chosen location. Available = `ICityDeckCard` + `!isControlled()` + `canBeDiscardedFromCity()` at that location (Carnaval `Action_01112b` idiom, scoped to location).
- ActionResolved only after discard/pass or when skipping discard — keep "Then you may" inside the action, not Pattern-L trailing-after-resolve.
- Stash target location in `Game::CHOSEN_LOCATION`.

## Art check
JPG: Initiative 4 (sun), Panache +1 (hat), Traits Camaraderie + Duty (both in TraitNames). Scaffold fields correct.

## Files shipped
- `modules/php/cards/bas/_04015.php` — resolve + IHasActions
- `modules/php/cards/bas/actions/Action_04015.php` — SchemeAction, name-matched Dietrichs, optional discard
- `modules/php/States/bas/State_planningPhaseResolveSchemes04015.php`
- `modules/php/States/bas/State_highDramaPhase04015.php` / `_2`
- `States.php` `2604015`, `404015`, `4040152`
- `states.inc.php` resolve `"04015"` + HD `"04015"` / `"04015_2"`
- JS bas triple + `PlayerActions.js` actionMap for two-location resolve

## Unfinished / watch
- No playtest yet.
- Availability requires ≥1 Dietrich — if Eddie wants discard-only when neither Dietrich is in play (strict reading of complete-as-much-as-possible), loosen the gate.

## Skill update (create-scheme)
Eddie asked to fold `_04015` learnings into create-scheme:
- SKILL.md: split `<b>Action:</b>` vs `<b>City Action:</b>` rows; Pattern M shape row; `_04015` in two-location refs + combination paragraph
- actions.md: `SchemeAction` in base table + WHY not City Action; full Pattern M section
- helpers.md: Action vs City Action; Name matching across reprints; uncontrolled = Controller 0; complete-as-much-as-possible; optional discard; Available City Card helper note
- checklist 34–36; references `_04015` / Action / States / `Action_01112b`; walkthrough `_04015`

WHY call out Name matching and SchemeAction hard: next agent will hardcode `_01035`/`_01036` (misses FAF reprints) or reach for `SchemeCityAction` (gates on city characters — Home Dietrichs would make the action unavailable).
