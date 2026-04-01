# Shifting Tides (_01151) Audit

## Card Text
> When this scheme is revealed, discard all City Cards from each City location. ("When reveal" is before any other scheme.)
> Add a City Card to each City location. Then, discard all Renown from all locations and add a Renown to a location. Then, each opponent adds a Renown to a different location. (During normal initiative order.)

Scheme card, Initiative=1, PanacheModifier=1, Nature trait. Two-phase effect: When Revealed + Resolve.

## Bug 1: `argsFromCard` not filtering already-chosen locations

In state `01151_2` (opponent picking), the args sent to the client included ALL city locations — even ones already chosen by the controller or a prior opponent. Server validation rejected invalid picks, but the UI showed them as selectable. This meant opponents would click a location, get an error, and have to try another.

Fixed by filtering `$this->locations` out of the `locationIds` array when in state `01151_2`. For state `01151` (controller's pick), no filtering needed since there are no restrictions — `$this->locations` is empty at that point anyway.

WHY the filter uses `array_values(array_filter(...))`: `array_filter` preserves keys from the original array, which can cause JSON encoding to produce an object `{1: "Forum", 3: "Bazaar"}` instead of an array `["Forum", "Bazaar"]`. `array_values` reindexes to ensure proper JSON array output.

## Bug 2: Missing `hasWhenRevealedEffect()` override

The base `Scheme` class has `hasWhenRevealedEffect()` returning `false`. The card clearly has a "When Revealed" effect but never overrode this. The mechanism in `stPlanningPhaseResolveWhenRevealedCards()` that uses it is currently skeletal (just counting, comments about what to do), so this doesn't cause a functional problem *today*. But it should be correct for when that mechanism is fully implemented.

## Verified Correct

- **City card filter**: `!$card->isControlled() && $card instanceof ICityDeckCard` works because `getAllCards()` only loads cards at city locations, player home, purgatory, dueling line, hand, and discard piles — NOT city deck or city discard. Uncontrolled ICityDeckCards in the collection can only be at city locations.
- **Renown removal**: Iterates `getCityLocations()` and removes all Renown from each. "All locations" in card text means all city locations (Renown only exists at city locations).
- **"Different location" enforcement**: `$this->locations` tracks controller's choice AND all opponent choices. Each pick is validated against this list. Correct — "a different location" means different from all previously chosen.
- **Turn order**: Opponents are queried `ORDER BY turn_order` for creating transition events, matching "during normal initiative order."
- **Persistence**: `$this->locations` survives between requests because Card objects are PHP-serialized into DB. `IsUpdated = true` triggers auto-save in `runEvents()`.
- **Reset**: `EventPhasePlanningEnd` clears `$this->locations = []` — prevents stale data between days.
- **City card drawing**: `getCardsOnTopOfCityDeck()` handles empty deck by shuffling city discard pile back in.
- **Constructor**: Initiative=1, PanacheModifier=1, Nature trait — all correct.

## Note: "Before any other scheme" priority

The card text says the When Revealed effect fires "before any other scheme." The current implementation fires it via `EventSchemeCardRevealed` during `stPlanningPhaseApproachCardsPlayed()`, where all reveals are queued in turn_order with default priority. If _01151's owner is later in turn order, their reveal fires after others'.

In practice this doesn't cause problems because no other scheme currently has a When Revealed effect that interacts with city cards. The skeletal `stPlanningPhaseResolveWhenRevealedCards()` is meant to handle ordering of When Revealed effects but isn't implemented yet. When it is, the `hasWhenRevealedEffect()` override will be relevant.

## Note: Double eventCheck pattern

The code calls `eventCheck()` explicitly then `queueEvent()` (which also calls `eventCheck` internally). This pattern exists in 60+ places across the codebase. It's an established convention, not a _01151-specific issue.
