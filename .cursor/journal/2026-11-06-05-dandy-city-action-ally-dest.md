# Dandy Action a → City Action, ally destinations

Printed text already said City Action + move to a City where you control a character. Code still treated it as a plain Action that could fire from Home and move to any other city.

## Change
- `Action_05Dandya`: `cardInCity` gate; `getDestinations` requires ≥1 character controlled by the player at the destination (not Claude's current location).
- Zombie picker in `State_highDramaPhase05Dandya_2` mirrors the same destination filter (was "any other city").
- Prompt copy updated. Skill references / pattern-c pay-N note updated.

## WHY Claude does not satisfy the destination
He is not at the destination yet. Counting him would make "any other city" legal again whenever he is alone in the city, which is the opposite of the nerf.

## WHY not control-of-location
Text is "where you control a **character**", not "a location you control". Ally present is enough even if the city is uncontrolled / opponent-controlled.

## Tests
`Card_05Dandy_Test` — Home unavailable; alone-in-city unavailable; ally at Forum enables; empty Bazaar rejected on pick. 27/27 PASS.
