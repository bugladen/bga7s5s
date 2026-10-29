# Are CLAUDE.md test suite docs updated?

User asked. Quick audit vs `tests/regression/`.

## Verdict
Card ID ranges in CLAUDE.md / `run-regression-tests.mdc` match disk:
- core `_01006`–`_01206` (441 files, 201 unique IDs = full contiguous span)
- tac `_02001`–`_02061` (137 / 61)
- faf faction `_03001`–`_03073` (191 / 73)
- bas faction `_04001`–`_04060` (156 / 60)
- city `_03cd01`–`_03cd21` / `_04cd01`–`_04cd29` as endpoints — every city card file that exists has a suite; gaps in numbering are missing cards, not missing tests

## Slightly stale
- `framework` described as “state-machine/`StatesTrait`” only — folder also has ArgumentsTrait, DeckTrait, EventHub, FrameworkActions, Theah, UtilitiesTrait (34 files)
- `cad`=05 listed in folder map but “Currently covers …” omits CAD (10 named suites: Dabney, Gary, Hulk, Teens, etc.; not all `_05*` cards have tests)
- 4 non-card suites still at `tests/regression/` root (as docs say)

## Docs update (follow-up)
User asked to update. Synced CLAUDE.md + `run-regression-tests.mdc`:
- `framework` lists StatesTrait/FrameworkActions/ArgumentsTrait/DeckTrait/EventHub/Theah/UtilitiesTrait
- coverage line adds partial cad `_05*` named set

