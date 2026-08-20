# planningPhase — disable approach cards after Confirm

## Report
After choosing scheme/character and clicking Confirm Approach Cards (`actEndPlanningPhase` → `onPlanningCardsSelected` → `actDayPlanned`), remaining approach deck cards stayed selectable while waiting for other players.

## Why it happened
`planningPhase` is `multipleactiveplayer`. Selection is enabled in `OnUpdateActionButtons` (`setSelectionMode(2)`). Cleanup lives in `onLeavingState` (`setSelectionMode(0)`), which only runs when the *state* ends — i.e. after every player has submitted. A finished player is non-multiactive but still in `planningPhase`, so selection mode stayed on.

## Fix
In `onPlanningCardsSelected`, immediately:
1. `approachDeck.setSelectionMode(0)` — no more selecting remaining cards
2. disable the confirm button — avoid double-submit while AJAX is in flight

WHY do it in the click handler (not onLeavingState): leaving only fires for the whole table. Per-player lockout has to happen at submit time.

Did not move card removal into the success `.then()` — that was pre-existing optimistic UI (and server parks the cards anyway so F5 is fine).
