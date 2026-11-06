# Action_05Dandyb → Covert Action

Printed text already said Covert Action. Code treated it as a plain in-city Collect (any city with Renown).

## Change
- Availability: gate on uncontrolled City (`!$cityLocation->isControlled()`), same as Valeri / Overcoat / Forsythe / Teens.
- Resolve: re-throw if control flipped between announce and pay (Forsythe shape) — before wealth is taken.
- Name prefixed `Covert:`. Skill pattern-c / references updated.

## WHY uncontrolled, not "you don't control"
Covert means the site has no controller. Opponent-controlled and self-controlled are both illegal.

## Tests
`Card_05Dandy_Test` — controlled Docks unavailable; uncontrolled restored. Full suite after change.
