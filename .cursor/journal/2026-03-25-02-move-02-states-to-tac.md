# Move 02* State Files to tac Subdirectory

## What
Moved 24 state files associated with `_02*` card classes from `modules/php/States/` to `modules/php/States/tac/`. Updated the namespace in each from `Bga\Games\SeventhSeaCityOfFiveSails\States` to `Bga\Games\SeventhSeaCityOfFiveSails\States\tac`.

## WHY
The `tac` subdirectory was already established for the 02020 (Yield) state files. The remaining 02* state files were still in the parent `States/` directory, inconsistent with the organizational pattern. This move aligns all Tactics & Cunning (tac) card states under the same subdirectory.

## Files Moved (24 total)
- State_duelChooseTechnique02006, 02011
- State_highDramaPhase02001, 02001_2, 02002, 02002_2, 02002_3, 02007, 02008, 02008_2, 02010, 02010_2, 02010_3, 02013, 02013_2, 02014, 02014_2
- State_planningPhaseResolveSchemes02005, 02005_2, 02005_3, 02005_4, 02005_5, _02014, _02015

## Excluded
- `State_highDramaPhase01102.php` — this is an `011xx` card, not `02*`, so it stays in `States/`.

## Note
The `tac` subdirectory now has 27 files total (24 moved + 3 existing 02020 files).
