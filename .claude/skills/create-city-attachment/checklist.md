> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## When You Finish

1. Re-read the card text and walk through each clause. Each clause should map to exactly one pattern (Forced / Passive / Action / Reaction / Steady-state / Custom-state). Stat modifiers go on the constructor and are not a "pattern." Multiple Action clauses → multiple Action classes.
2. If you added a custom state, verify all three are present: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`. Confirm any rerouted transitions still match the new entry point. (No `states.7s5s.php` for new GameState subclasses.)
3. If this is the **first** card for an expansion, also create and wire `OnEnteringState.<exp>.js` / `OnUpdateActionButtons.<exp>.js` / `OnLeavingState.<exp>.js` (see [wiring.md](wiring.md)).
4. If you minted a new event class, register it in `Events.php` (constant), `EventHub.php` (handler — usually no-op), and `EventFactory.php` (factory method).
5. If you minted a new global, clear it in the matching cleanup state (e.g. duel-scoped globals clear in `stDuelEndOfRound`).
6. Verify JS wiring for any new state you added.
7. Mentally run the pre-commit hook checks against the files you touched. Especially: `createActionResolvedEvent` in actions (call or comment), `setUsed`/`isAvailable` in reactions, no `setUsed` in `AttachmentAction` subclasses. **Do not** set Riposte on CityAttachment.
8. Sink vs destroy: **"Sink this card"** (the attachment) → City Deck bottom; **"Destroy this card"** → city discard. Do not mix with FactionAttachment's faction-deck sink. Mid-action **"sink any"** of looked-at **faction** deck cards → immediate `insertCardOnExtremePosition(..., false)` before reorder (not queued events — race).
9. Multi-step + sink/destroy cost on the attachment: pay sink at commit, not on trigger; no Back on the first picker after in-play confirm.
10. Engage-this-card timing: location/opponent picker → pay engage on commit; effect starts immediately (deck look) → pay engage on `EventActionTriggered`.
11. Deck look / sink any / reorder: Pass on sink = sink none; auto-skip reorder when 0 or 1 remaining; empty look may skip via a second EVENTS transition (register it). Optional discard-to-draw: `createActionResolvedEvent` on **both** discard and Pass; notify decline as well as accept. Wire chooseList + hand EventHandlers.
12. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md` covering the WHY: which existing patterns/flags you reused, what alternatives you considered, anything that looks weird (variable-name landmines, defensive resets, event-priority gotchas). Read the related faf / bas journals first — they encode hard-won knowledge about edge cases (zombie handling, copied-action crashes, event ordering inside handleEvent, RiskClone timing, sink+reorder races).
