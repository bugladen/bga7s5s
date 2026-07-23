> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## When You Finish

1. Re-read the card text and walk through each clause — confirm each maps to exactly one branch you wrote. If every clause is Forced, stop at the card class (no Action/State/JS) — see `bas/_04cd07`.
2. For every new state class, verify all three are present: the class file, the `States.php` constant, and the entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS` transitions.
3. For every new state, verify JS wiring in the expansion files (`OnEnteringState.faf.js` / `OnEnteringState.bas.js`, matching `OnUpdateActionButtons` / `OnLeavingState`). Add to `PlayerActions.js` action map if reusing client actions.
4. Mentally run the pre-commit hook checks against the files you touched.
5. For reveal Forced: confirm `EventCityCardAddedToLocation` + `cardId == $this->Id` (not `cardInCity`). For "does not control this location" effects: do **not** copy Equal Claim's `!$location->isControlled()` early-return unless the effect is specifically "become uncontrolled."
6. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY (which existing flag/pattern you reused, what alternatives you considered, anything that looks weird). Read the related faf/bas journals first — they encode hard-won knowledge about edge cases (zombie handling, priority ordering, `CURRENT_PLAYER` vs active player, uncontrolled-location eligibility).
