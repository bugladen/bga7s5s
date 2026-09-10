# End-of-game / deck / day statistics (BGA stats.json)

## Context
First real BGA statistics for this game. Docs: https://en.doc.boardgamearena.com/Game_statistics:_stats.json

## Stats
- Table `day_ended` (id 10): day game ended
- Table `victory_type` (id 18): how the game was won — labeled int
- Player `renown_ended` (id 11): renown at end
- Player `leader` (id 12): which Leader brought — labeled int
- Player `characters_day_1`…`characters_day_5` (ids 13–17): controlled characters in play at that day's dusk end
- Player `wounds_received` (id 19): cumulative wounds applied to characters the player controlled

## Victory type labels (id 18) / Game::VICTORY_*
0 None, 1 Assassination, 2 Dominance, 3 Economic, 4 Fifth Day

WHY id 18 not 12: value_labels are shared across table+player for the same id; 12 is already Leader labels.

WHY Fifth Day exists though user listed 3: `stPlunderCheckFifthDayVictory` still ends the game; without a label those exits would wrongly stay None.

Set via `goToEndOfGame($type)` / `recordEndOfGameStats($type)`:
- Dominance → VICTORY_DOMINANCE
- Economic → VICTORY_ECONOMIC
- Fifth day (all tiebreak exits) → VICTORY_FIFTH_DAY
- 2p Leader destroy EventTransition → VICTORY_ASSASSINATION

## End-of-game path
Cannot hook stGameEnd (framework forbids overloading).

## Leader labeled stat
`Game::LEADER_STAT_LABELS` must match stats.json order. Set in `DeckTrait::buildDecks()`.

## Characters-per-day
`recordCharactersAtEndOfDayStat()` in `stDuskEndOfDay` before Brute discard.
Inited to 0 in setupNewGame.

## Wounds received
WHY cumulative via `playerStats->inc` in `Character.php` on `EventCharacterWounded` (after wound reductions), not an end-of-game sum of current `Wounds`: "received" includes healed wounds and destroyed characters. Tallied for `ControllerId` when wounds apply. Init to 0; no need to set in `recordEndOfGameStats`.

## Renown timing
Dominance / 2p Leader kill set losers to -1 before end; that is what renown_ended records.

## Do not regress
- New endOfGame exits must pass the correct VICTORY_* type
- New Leaders → append to BOTH LEADER_STAT_LABELS and value_labels (never reorder once live)
- Do not change live stat ids / do not edit stats.json while public games run
- Wound prevention that zeroes `EventCharacterWounded::wounds` before Character handles it must stay before Character's handler so the inc stays accurate
