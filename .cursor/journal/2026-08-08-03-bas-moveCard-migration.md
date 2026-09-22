# bas branch: apply Game::moveCard pattern

Eddie switched to a branch with `modules/php/cards/bas` and asked to apply the
new moveCard helpers there.

## What was in bas

Only two raw `$deck->moveCard` sites:

1. `Action_04cd01b.php` — hide original risk when cloning (mirrors Action_01106)
2. `_04cd01_RiskClone.php` — hide the clone after it resolves (mirrors _01106_RiskClone)

Both are real location changes to PERMANENTLY_HIDDEN → `Game::moveCard(..., $card)`.

## What was not changed

`getGameDeckObject()` + `insertCardOnExtremePosition` in Technique_04001 /
Action_04cd15 — reordering within the faction deck; Location already is
Faction-*, so no sync issue. Not part of the moveCard pattern.

`getCardsInLocation` via deck object — read-only, fine.

## Verify

No remaining `($deck|$this->cards|$deckObject)->moveCard(` under
`modules/php/cards/bas`.
