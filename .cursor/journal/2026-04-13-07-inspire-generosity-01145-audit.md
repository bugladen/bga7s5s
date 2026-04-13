# Inspire Generosity (01145) Audit

## Card Text

> Move a Renown from a location to another location. Then, add a Renown to each location that has none.
>
> Each player draws a card. Then, the player with the least Renown draws a card. Then, the player with the fewest characters draws a card. (Least and fewest cannot tie.)

## Result: Clean - No Issues Found

This is a Scheme card with a 3-state flow:

1. **State 01145** (activeplayer): Pick a city location with renown to move FROM. Pass allowed only if no locations have renown (all 0). Frontend correctly filters to only locations with renown > 0.

2. **State 01145_2** (activeplayer): Pick a city location to move TO. Frontend excludes the from-location via `argsFromCard` passing `chosenLocation`. Queues renown remove and add events.

3. **State 01145_3** (game): Auto-runs. Does the remaining effects in order:
   - Adds renown to all locations that have none (adjusting for the pending move that may not have resolved yet)
   - Each player draws a card
   - Player with least Renown (no tie) draws a card — uses `ORDER BY player_score`, compares bottom two
   - Player with fewest characters (no tie) draws a card — uses `getPlayerControllingFewestCharacters()` which returns null on tie

### Renown Adjustment in State 3

The code adjusts `$location->Renown` by +1 for toLocation and -1 for fromLocation before checking for 0. This accounts for the move events queued in state 2 that may not have been processed yet. Same pattern as other multi-step scheme resolutions.

### Tie Handling

Both "cannot tie" clauses handled correctly:
- Renown: compares two lowest `player_score` values, only draws if different
- Characters: `getPlayerControllingFewestCharacters()` sets playerId to null when two players share the lowest count
