# Deck list / Leader loading at setup (search)

## Flow (WHY order matters for a Leader stat)
`setupNewGame` does NOT load decks. It only inserts players, globals, and inits existing stats. Deck JSON lands later:

1. `States::GAME_SETUP` → `States::DECK_ASSIGNMENT` (`State_DeckAssignment::onEnteringState`)
   - Random: pick from `StarterDecks::$decksJson`, write `player.deck_source`
   - Manual: transition `pickDecks` → `actPickDeck` writes `deck_source`
   - Tournament: restore from `bga->tournament->retrievePlayerGameData(..., 'deck_source')`
2. `States::BUILD_TABLE` → `stBuildDecks()` → `DeckTrait::buildDecks()`
   - Reads `deck_source`, creates Leader from `$deck->leader`, approach + faction decks

## Best hook for "which Leader" player stat
`DeckTrait::buildDecks()` immediately after Leader create / `leader_card_id` UPDATE / `playLeader` notify (~lines 45–64). At that point you have `$playerId`, `$deck->leader` (card code string), and `$card->Name` (translated).

Do NOT set in `setupNewGame` — Leader does not exist yet.
Do NOT rely only on `actPickDeck` — random / tournament paths skip it.

## Leader identity
- Deck JSON field: `leader` = card id string (`"01089"`, `"03001"`, …)
- Instantiated via `createCardInLocation($deck->leader, LOCATION_PLAYER_HOME, …)`
- Persisted: `player.leader_card_id` = DB card row id (not the class code)
- Lookup: `Theah::getLeaderByPlayerId` reads that column

## Leaders found
10 classes: Core 01006/01035/01062/01089/01116; FaF 03001/03013/03025/03037/03049. No TaC `extends Leader` — TaC starters reuse Core leaders.
