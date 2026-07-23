> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting: Common Helpers

- `getCardsOnTopOfPlayerFactionDeck($playerId, $nbr)` — top of a faction deck, reshuffle-aware.
- `getCardsOnBottomOfPlayerFactionDeck($playerId, $nbr)` — added for `_03cd05`. BGA Deck library has no native bottom helper; this one sorts `card_location_arg` ASC and slices the first `$nbr`. Lower `card_location_arg` = bottom, higher = top (confirmed in `_ide_helper.php`).
- `insertCardOnExtremePosition($card, $location, $bOnTop)` — `bOnTop = true` means "place on top." Sinking after a top-reveal passes `false`. Sinking after a bottom-reveal passes `true` (cards sink to the top). **Variable-name landmine:** `$fromBottom` happens to align numerically with `$bOnTop`, but the semantics are unrelated — comment it where you pass it.
- `createCardAddedToCityDeckEvent($playerId, $cardId, $onTop)` — sink/add a city card to the City Deck. `$onTop = false` = bottom ("sink"). Used for **"Sink this card"** on CityAttachments (`Action_04cd01b`) and look-at sinks (Kaspar / Reaction_03052).
- `createCardAddedToCityDiscardPileEvent(...)` — **destroy** / discard a city card, not sink.
- `createCardInLocation($className, $location, $ownerId, $controllerId)` — mint a runtime card (RiskClone). `getCardClassName` routes by the first two characters of `$className` (`04` → `bas`). Always `addCardToWorld` after create when the clone must receive events.
- `getAdjacentCityLocations($location, $includeHome = true)` — pass `$includeHome = false` when text says **"City location"**.
