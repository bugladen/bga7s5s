# _04cd01 Penya (bas City Attachment)

## Card Text
- **Action:** Engage this card • Move the equipped character to an adjacent City location.
- **City Action:** Sink this card • Play target risk from an opponent's discard pile, paying all costs. After it resolves, sink it.

## Classification
1. Action → Pattern C AttachmentAction (engage attachment + location chooser). Exemplars: Action_01046a / Action_03055.
2. City Action → Pattern C AttachmentAction + Improvising (01106) RiskClone flow. Sink cost like Action_03065 but city deck (createCardAddedToCityDeckEvent onTop=false).

## WHY choices
- Two separate Action classes (Action_04cd01 move, Action_04cd01b play-risk) rather than one mega-action — matches cards with multiple printed abilities.
- Reuse 01106 clone machinery rather than inventing a new pay/play path — game expects in-hand Risk actions.
- Sink attachment at risk-commit (actFromActionWithActionId), not on EventActionTriggered — preserves back navigation from risk select to opponent select without losing the attachment. No back on opponent-select state (action already Used after in-play confirm); zombie only.
- RiskClone only sinks the played Risk after resolve (createActionResolvedEvent lives there, same as 01106). Attachment already sunk as cost.
- City Action gates on cardInCity; move Action also requires city + unengaged attachment + adjacent locations exist. includeHome=false for "City location".
- Hand-wealth affordability for City Action does NOT subtract owning-card wealth (unlike 01106) — attachment is not leaving the hand.
- First bas State/JS modules — create States/bas + On*State.bas.js and wire into seventhseacityoffivesails.js / OnEnteringState.js / etc.

## Pre-commit follow-up (same day)
- Riposte check no longer applies to CityAttachment (CityAttachment has no FactionCardTrait). Updated `.githooks/pre-commit`, CLAUDE.md, create-city-attachment skill. Removed the dummy Riposte property/assignment from `_04cd01`.

## Files touched
- `modules/php/cards/bas/_04cd01.php`
- `modules/php/cards/bas/_04cd01_RiskClone.php`
- `modules/php/cards/bas/actions/Action_04cd01.php`
- `modules/php/cards/bas/actions/Action_04cd01b.php`
- `modules/php/States/bas/State_highDramaPhase04cd01*.php` (3)
- `modules/php/States.php`, `states.inc.php`
- `modules/js/On*State.bas.js` (3) + wiring in main JS files
- `.githooks/pre-commit`, `CLAUDE.md`, create-city-attachment skill

## Status
Implemented. php -l clean. Not playtested on BGA Studio yet. Riposte pre-commit scope fixed.
