> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Cross-Cutting: Common Helpers

- `getCardsOnTopOfPlayerFactionDeck($playerId, $nbr)` — top of a faction deck, reshuffle-aware.
- `getCardsOnBottomOfPlayerFactionDeck($playerId, $nbr)` — added for `_03cd05`. BGA Deck library has no native bottom helper; this one sorts `card_location_arg` ASC and slices the first `$nbr`. Lower `card_location_arg` = bottom, higher = top (confirmed in `_ide_helper.php`).
- `insertCardOnExtremePosition($card, $location, $bOnTop)` — `bOnTop = true` means "place on top." Sinking after a top-reveal passes `false`. Sinking after a bottom-reveal passes `true` (cards sink to the top). **Variable-name landmine:** `$fromBottom` happens to align numerically with `$bOnTop`, but the semantics are unrelated — comment it where you pass it.
- **Mid-action sink + reorder of looked-at faction-deck cards** (`Action_04cd15`, Maneuver_03059): use **immediate** `insertCardOnExtremePosition($id, $deckName, false)` for sinks, then top-inserts for replace/reorder. WHY: queued `createCardAddedToFactionDeckEvent` races the reorder top-inserts before EVENTS drains. Do not "improve" this to events without fixing the race.
- `createCardAddedToCityDeckEvent($playerId, $cardId, $onTop)` — sink/add a city card to the City Deck. `$onTop = false` = bottom ("sink"). Used for **"Sink this card"** on CityAttachments (`Action_04cd01b`) and City Deck look-at sinks (Kaspar / Reaction_03052). **Not** for mid-action faction-deck sink+reorder (see immediate insert above).
- `createCardAddedToFactionDeckEvent($playerId, $cardId, $onTop)` — faction-deck add/sink when **not** racing a same-tick reorder (safe for end-of-effect sinks).
- `createCardAddedToCityDiscardPileEvent(...)` — **destroy** / discard a city card, not sink.
- `createCardDiscardedFromHandEvent(..., $asEffect = true)` + `createCardDrawnEvent($playerId, $reason)` — optional "discard a card to draw a card" (`Action_04cd15_3`). Log decline on Pass as well as success on discard.
- `createCardInLocation($className, $location, $ownerId, $controllerId)` — mint a runtime card (RiskClone). `getCardClassName` routes by the first two characters of `$className` (`04` → `bas`). Always `addCardToWorld` after create when the clone must receive events.
- `getAdjacentCityLocations($location, $includeHome = true)` — pass `$includeHome = false` when text says **"City location"**.
