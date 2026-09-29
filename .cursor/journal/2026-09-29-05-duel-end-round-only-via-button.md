# Duel end-of-round: only via actDuelDoneRound

Eddie asked whether code can end a duel round, or only the End Round button.

## Answer
Only `actDuelDoneRound` (and Vladislav's `actDuelEndDuel`) take transition `doneWithRound` → `DUEL_END_OF_ROUND`. That is the sole entry into `stDuelEndOfRound`, which is the only place that queues `EventDuelEndOfRound`.

Callers:
- UI End Round button → `actDuelDoneRound`
- Zombie AI on duel choose states → same method (stand-in for a click)
- Vladislav End Duel → `actDuelEndDuel` → same transition (skips DuelActionsDone)

Cards react to `EventDuelEndOfRound`; they do not create the entry into end-of-round. Mid-round death does not auto-end the round — player still has to click End Round.

## WHY this matters for 03022
Final Strike's En Garde chooser (`DUEL_END_OF_ROUND_03022`) is wired under `DUEL_END_OF_ROUND_EVENTS` transitions. So the death-triggered transition to `"03022"` is meant to run in the end-of-round event cascade — after the player has ended the round — not as a way to force the round to end.

## Follow-up: End Round log message
Eddie asked again for a log specifically in `actDuelDoneRound` (immediate, not deferred via event queue).
Added: `${player_name} ends the round.` after round-1 validation, before queueing EventDuelActionsDone.
EventHub still has the later `${player_name} is done with their actions for the round.` — will double-log; left as-is unless he asks to remove one.
