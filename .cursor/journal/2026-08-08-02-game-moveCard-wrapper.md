# Game::moveCard wrapper — kill the Location-drift class

## What Eddie confirmed

Yes: wrap deck `moveCard` in a Game helper that updates both the deck row and
serialized `Card->Location`. Do it.

## Design

Three helpers on `DeckTrait` (Game):

1. **`moveCard($id, $location, $locationArg = 0, ?Card $card = null): Card`**
   — deck move + set Location + persist. Prefer the in-world card instance so
   Theah stays coherent. This is the default path.

2. **`moveCardInDeck($id, $location, $locationArg = 0): void`**
   — deck row only. For:
   - premature deck moves before a queued EventCardMoved /
     EventCityCardAddedToLocation (so same-request location queries don't see
     the card at the old pile, while Location is set by the event)
   - destroy+recreate paths where Location is written on the fresh instance

3. **`parkCard($id, $holding = PURGATORY): void`**
   — convenience over moveCardInDeck for Purgatory holds that must leave
   Location reporting Hand (Maxime 01069 step 1, Matchlock 01156, day planning,
   dusk discard parking).

WHY not a single method with `$updateLocation = true` flag: the false case is
rare and intentional; a named method makes the WHY visible at the call site
instead of a boolean that future agents will "clean up."

WHY still keep `repairDiscardPileLocations` in buildCity: in-flight games
already have drift, and any future miss still self-heals for discard piles.
Belt and suspenders.

## Migration

Migrated every raw `$deck->moveCard` / `$this->cards->moveCard` outside
DeckTrait. Card files, EventHub, FrameworkActionsTrait, StatesTrait,
UtilitiesTrait. bas/ 04cd01 files aren't on this branch.

Also fixed a latent drift in EventCardRemovedFromPlayerDiscardPile's
`permanentlyHide` branch — it moved the deck row and never touched Location.

## Guardrail

Pre-commit now rejects raw `->moveCard(` on `$this->cards` / `$deck` /
`$deckObject` outside DeckTrait.php. CLAUDE.md pre-commit table updated.

## Feelings

This is the right structural fix for yesterday's stuck-game bug. The repair
layer alone would have been a forever-band-aid. Slightly nervous about
EventHub paths that set ControllerId/Engaged AFTER moveCard's immediate
persist — there is a brief DB window with new Location and old other fields —
but that's within a single request before the IsUpdated flush, same risk
profile as before for practical purposes. If something weird shows up, look
there first.

Not verified on a live game. No local runner.
