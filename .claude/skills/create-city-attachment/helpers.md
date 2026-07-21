> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting: Common Helpers

- `getCardsOnTopOfPlayerFactionDeck($playerId, $nbr)` — top of a faction deck, reshuffle-aware.
- `getCardsOnBottomOfPlayerFactionDeck($playerId, $nbr)` — added for `_03cd05`. BGA Deck library has no native bottom helper; this one sorts `card_location_arg` ASC and slices the first `$nbr`. Lower `card_location_arg` = bottom, higher = top (confirmed in `_ide_helper.php`).
- `insertCardOnExtremePosition($card, $location, $bOnTop)` — `bOnTop = true` means "place on top." Sinking after a top-reveal passes `false`. Sinking after a bottom-reveal passes `true` (cards sink to the top). **Variable-name landmine:** `$fromBottom` happens to align numerically with `$bOnTop`, but the semantics are unrelated — comment it where you pass it.
