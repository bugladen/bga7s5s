# Status Matters 01086 bare nextState bug

## Bug
Confirm Location in `highDramaPhase01086` threw: "More than one possible transition at this state".

Empty location (no mercenaries) is a red herring — any confirm hit the same path.

## Cause
GameState conversion (2026-11-02) added `"zombie" => EVENTS` alongside `"locationChosen"` because old ZombieTrait used bare `nextState()` with only one named transition.

`Action_01086::actFromActionWithIds` was left on bare `$game->gamestate->nextState()`. With two keys, BGA refuses the empty transition.

Sibling `Action_04060` already used `nextState("locationChosen")`.

## Fix
- Action: `nextState("locationChosen")` + WHY comment
- Tests: assert `['locationChosen']` instead of `[null]`

## Lesson
When adding a zombie (or any second) transition during GameState conversion, grep the card's Action/Reaction/Maneuver for bare `nextState()` and name them. The conversion journal noted the zombie WHY but didn't update Action_01086.

## Follow-up audit (same session)
User asked if conversion introduced more of these.

Scanned 242 multi-transition GameStates for bare `nextState()` gated on that state's `States::` const (cards + StatesTrait/Game). **0 remaining bugs** after the 01086 fix.

Conversion journals only **Added** zombie on three cards:
- **01055 / 01076**: Actions already used named `characterChosen`/`locationChosen` — safe
- **01086**: bare nextState — the one we fixed

Lots of bare `nextState()` still exist elsewhere, but those hit sole `""` transitions (legal). Unclassified multi-transition leftovers (`setupTable_01006`/`05Gary`, `planningPhaseApproach01200`, `DeckAssignment`) already use named transitions in handlers.
