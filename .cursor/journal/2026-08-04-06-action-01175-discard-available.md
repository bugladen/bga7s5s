# Action_01175 showing while card in discard

## Bug
Tending the Wounded (`Action_01175`) appeared in available in-play actions even when the Risk was in the discard pile.

## Root cause
`Action_01175` extended `CardAction` instead of `RiskAction`.

`Theah::getInPlayActionsAvailableToPlayer` iterates cards with `Location != LOCATION_HAND` (discard included) and includes any action whose `isAvailableToPlayer` returns true.

- `CardAction::isAvailableToPlayer` — controller + `!Used` only; **no location check**
- `RiskAction::isAvailableToPlayer` — requires owning card `Location == LOCATION_HAND`

So a discarded Risk with a bare `CardAction` still looked available if there was a wounded non-Leader and cards in hand.

## Fix
Changed `Action_01175` to extend `RiskAction` (same pattern as every other Risk Action, e.g. `Action_01176`). Parent hand check now gates availability.

WHY not add an ad-hoc location check on `Action_01175`: wrong base class was the bug; Risk actions belong on `RiskAction`.

## Note
Earlier audit (`2026-04-01-04-tending-wounded-01175-audit.md`) marked this card clean and never checked the base class / discard path. Lesson: for Risk Actions, verify `extends RiskAction` and that discard/in-play enumeration cannot surface them.

## Status
Fixed. No other files touched.
