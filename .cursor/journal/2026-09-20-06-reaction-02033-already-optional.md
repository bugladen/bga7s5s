# Reaction_02033 — already optional (no code change)

Eddie corrected 02035 → 02033 (“Prima” Rosa). Same ask: controlling player should get an option, not auto-trigger.

## Finding
`Reaction_02033` already does that — since the original `4340eda9` implementation:

1. `EventCardMoved` → `createReactionTransitionEvent($rosa->ControllerId, …)`
2. Rosa’s controller sees **Have Them Discard** / **Pass**
3. Only on Have Them Discard does the mover’s controller get discard-hand buttons
4. Pass clears `pendingMoverCharacterId` without `setUsed`

Printed text is `<b>Reaction:</b>` (not Forced). Card image TAC-33 matches.

## WHY no edit
Changing a working Use/Pass flow “to make it optional” would risk the Blood Mark once-per-ability / dusk-persist work from `2026-07-31-06`. Ask Eddie for a repro if play still skips Rosa’s prompt (e.g. jumps straight to mover discard).

## Related
Prior lookup: `2026-09-20-05-reaction-02035-lookup.md` (02035 doesn’t exist).
