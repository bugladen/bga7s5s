## Reaction_02033 vs Action_01076 (Blood Mark) double trigger

### Question
If Blood Mark moves two characters onto Rosa's location, does Rosa's reaction fire twice? Eddie: should only fire once.

### What Action_01076 does
Queues (same `actFromActionWithId`): wound (optional) → `EventCardMoving` performer → `EventCardMoving` second character → `ActionResolved` → `SorcererAbilityPlayed`. Both moves share `abilityId = Action_01076`.

Hub converts each Moving → `EventCardMoved` (MEDIUM=3). Rosa's reaction transition is REACTION_PRIORITY=6, so both Moved events run before the prompt.

### Why pending alone was thin
`pendingMoverCharacterId != 0` blocks a second trigger while a prompt is open / while both Moved events share one `runEvents` pass. That covers today's batched Blood Mark. It does **not** cover:
- After-reaction sequencing (rulebook: After reactions resolve before the next effect of a multi-effect ability) — if move1 → Rosa UI → clear pending → move2, she'd fire again
- Any path where pending is cleared before a same-ability second `EventCardMoved`

### Fix
Added `$consumedAbilityId`:
- On first successful Rosa trigger for a move with non-empty `abilityId`, store that id
- Ignore further `EventCardMoved` with the same `abilityId`
- Clear on `EventActionResolved` (so Repeatable abilities with the same id can trigger again later the same day) and on dusk

Kept `pendingMoverCharacterId` for empty-`abilityId` / in-batch cases.

Also: dusk clear of pending/awaiting/consumed now sets `IsUpdated` after parent `setUsed` (which already wrote the card) so cleared flags actually persist.

### Not changed
Action_01076 still wounds before moving (card text says move, then wound, then maybe bring a second). Reordering would make After-reaction gaps more likely; `consumedAbilityId` is what keeps Rosa once-per-ability if that ever gets fixed.
