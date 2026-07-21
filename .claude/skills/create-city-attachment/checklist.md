> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## When You Finish

1. Re-read the card text and walk through each clause. Each clause should map to exactly one pattern (Forced / Passive / Action / Reaction / Steady-state / Custom-state). Stat modifiers go on the constructor and are not a "pattern."
2. If you added a custom state, verify all three are present: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`. Confirm any rerouted transitions still match the new entry point.
3. If you minted a new event class, register it in `Events.php` (constant), `EventHub.php` (handler — usually no-op), and `EventFactory.php` (factory method).
4. If you minted a new global, clear it in the matching cleanup state (e.g. duel-scoped globals clear in `stDuelEndOfRound`).
5. Verify JS wiring for any new state you added.
6. Mentally run the pre-commit hook checks against the files you touched. Especially: `createActionResolvedEvent` in actions, `setUsed`/`isAvailable` in reactions, no `setUsed` in `AttachmentAction` subclasses.
7. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY: which existing patterns/flags you reused, what alternatives you considered, anything that looks weird (variable-name landmines, defensive resets, event-priority gotchas). Read the related faf journals first — they encode hard-won knowledge about edge cases (zombie handling, copied-action crashes, event ordering inside handleEvent).
