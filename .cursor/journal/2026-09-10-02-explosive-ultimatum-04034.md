# Explosive Ultimatum (_04034)

Implemented BAS scheme Explosive Ultimatum via create-scheme skill.

## Classification
1. **Resolve (Pattern A):** Add Renown to any location. If fewest Renown, may move Renown to adjacent instead.
2. **City Action:** Opponent who controls performer's location may lose control; if they do not, wound all opposing chars there.

## Art vs scaffold
- Initiative 28 / Panache 0 / Cunning+Sabotage — match art (Traits already in TraitNames).
- Art text has **"may"** move instead; scaffold omitted may. Fixed Text.
- Art: "choose"; scaffold typo "chose". Fixed.
- Fewest for move-instead: **no ties**. Eddie clarified — unique fewest only (`playerHasFewestRenown` requires `$atLowest == 1`), same as `_01144` even without the printed parenthetical.

## Resolve UX (mirror `_01152` gated by fewest)
- Always: city location pick → add Renown (`renownPlaced`).
- If fewest AND any location has Renown>0: also "Move a Renown Instead" via `actFromCardPass` → source pick → adjacent dest.
- When not fewest / no movable Renown: no move button (must add).
- Three planning states 04034 / _2 / _3. Named transitions when Back present.
- PlayerActions.js `onPass` map for `planningPhaseResolveSchemes_04034` → `actFromCardPass`.

## City Action
- Performers: at location where `Controller` is an opponent (`!= 0 && != playerId`).
- Transition to that controller as active player (`createTransitionEvent($controllerId, ..., "04034", $this->Id)`).
- Buttons: Lose Control (id 1, if canBecomeUncontrolled) / Decline (id 2, wound opposing).
- Opposing = ControllerId != acting player (scheme owner), at that location.
- `createLocationBecomesUncontrolledEvent` + `createActionResolvedEvent` on both paths.
- Zombie: lose control if legal, else decline.

## Files
- `cards/bas/_04034.php`, `actions/Action_04034.php`
- States: `State_planningPhaseResolveSchemes04034{,_2,_3}`, `State_highDramaPhase04034`
- `States.php` 2604034 / 404034; `states.inc.php` transitions
- JS bas triple + PlayerActions pass map

## Skill update (post-implementation)
Captured into create-scheme: Pattern A add/fewest-move-instead (no ties), Pattern N opponent ultimatum City Action, JS args nesting trap, PlayerActions pass map, `canLocationBecomeUncontrolledBy`. Files: SKILL.md, pattern-a.md, actions.md, helpers.md, checklist.md, references.md, wiring.md.
