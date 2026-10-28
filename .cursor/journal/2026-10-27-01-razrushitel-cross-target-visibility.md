# Razrushitel target visibility for Cross of the Martyrs

## Ask
Action announced + target chosen for Razrushitel (01138). Opponent could not tell
who was targeted and wanted Cross of the Martyrs (02016: "when an opponent targets
your character"). Need the target named in chat and/or reaction status bar before
they decide.

## Root cause
`Action_01138` implemented `IAbilityThatTargetsCharacters` but never fired
`EventCharacterTargeted` and never logged the pick. Engaged path queued move+wound
immediately; en garde path stored `CHOSEN_TARGET` and jumped to the engage chooser.
Cross only saw later wound/move events — status bar said "become the new target"
with no name.

Same class of hole as UL challenge intent log (2026-10-21-01) and Come Hither's
pre-refactor missing CharacterTargeted (2026-05-22-03).

## Fix
1. **Action_01138** — Come Hither / Amour pattern:
   - Target pick: announce (`uses ${card} targeting ${character}`), queue
     `EventCharacterTargeted` with batchId, go to events.
   - On survive: engaged → move+wound+resolve; en garde → transition `01138_2`.
   - WHY not effects beside the hook: Cross/UL hold only CharacterTargeted;
     siblings already in the queue would land before the reaction window and the
     player would never see who was targeted first.

2. **State_highDramaPhase01138** — sole transition `"" → EVENTS` (was wound /
   manipulate). Engage chooser reached via EventTransition `"01138_2"`. Registered
   that key on `HIGH_DRAMA_PLAYER_TURN_EVENTS` in states.inc.php.

3. **Reaction_02016** — `getReactionDescription` now sprintf's the targeted
   character's name (`instead of %s`). Covers every Cross offer path (CharacterTargeted,
   wound, move, engage, challenge) even when the ability never logged.

## Deliberately not done
No central EventHub announce for every CharacterTargeted — challenge already has
`announcePendingChallenge`, and hub would duplicate. Cross status bar + 01138's own
notify cover the reported case without log spam.

## Tests
Action_01138_Test + Reaction_02016_Test passed (38).
