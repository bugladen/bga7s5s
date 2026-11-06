# Dandy setup draw: 4 → 3

Card text already said "During setup, draw three cards." Code still had `min(4, $available)`. Print was nerfed; Text was updated earlier, draw loop was not.

## Fix
- `_05Dandy::drawSetupCards`: `min(3, …)`
- Test name + assert 4→3
- Skill references row: "setup draw 3"

WHY leave the rest alone: still no second shuffle; still cap with `getCardsInLocation` so empty test decks don't reshuffle discard. Only the N changed.

## Tests
`php tests/run.php tests\regression\cad\Card_05Dandy_Test.php` — 25/25 PASS.
