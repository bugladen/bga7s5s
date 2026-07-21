# La Voix des Sans Voix (_03034)

## Context

Eddie asked to finish `_03034` via create-risk. Prior session today was `_03033` Glorious + skill update. Card text is only a Diplomat City Action.

## Approach / WHY

**Pattern A RiskCityAction** with Diplomat trait gate + engage cost (`!Engaged`), then en garde another controlled character at the performer's location, then "may heal a wound / if they do not, draw a card."

WHY not invent a new pattern:
- Diplomat + engage cost → `Action_03030` / `Action_03021`
- En garde verb → targets must be `Engaged == true` (`createCardEngardedEvent`); skill When You Finish #16
- Friendly same-location chooser → `Action_03011` shape with location filter flipped to same location
- May / if they do not → `{id:1}` heal / `{id:2}` draw like `Action_01049_2`; if `Wounds == 0` auto-draw (cannot heal) like 01049's already-engaged auto-wound

Engage happens on `EventActionTriggered` before the target chooser — same as Cornered/Sworn Swords so the cost is paid when the action is announced, not when the ally is picked.

Used explicit "Draw a Card" button instead of Pass — the card text frames draw as the alternate branch, not skipping.

## Files touched

- `modules/php/cards/faf/_03034.php`
- `modules/php/cards/faf/actions/Action_03034.php` (new)
- `modules/php/States/faf/State_highDramaPhase03034.php` + `_2` (new)
- `modules/php/States.php` (403034 / 4030342)
- `states.inc.php` transitions
- `modules/js/On{Entering,UpdateActionButtons,Leaving}State.faf.js`

## Unfinished / watch

Not playtested. Worth verifying on BGA that engarde after engage-at-announcement still leaves the ally Engaged when the chooser args are built (events should flush before state 03034). Also Unique trait is already in TraitNames — no TraitNames edit needed.

Did not update create-risk skill in the implementation pass — Eddie later asked for the skill update (see `2026-07-11-03-create-risk-skill-03034.md`). Pattern A.3 now lives in the skill.
