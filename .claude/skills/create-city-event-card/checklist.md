> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## When You Finish

1. Re-read the card text and walk through each clause — confirm each maps to exactly one branch you wrote.
2. For every new state class, verify all three are present: the class file, the `States.php` constant, and the entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS` transitions.
3. For every new state, verify JS wiring in `OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`, and (if you set selection modes / styling) `OnLeavingState.faf.js`. Add to `PlayerActions.js` action map if reusing client actions.
4. Mentally run the pre-commit hook checks against the files you touched.
5. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY (which existing flag/pattern you reused, what alternatives you considered, anything that looks weird). Read the related faf journals first — they encode hard-won knowledge about edge cases (zombie handling, priority ordering, `CURRENT_PLAYER` vs active player).
