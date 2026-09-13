# Indomitable Will → Locker cleanup

User asked: if a character with `INDOMITABLE_WILL_CONDITION` goes to the locker, is the effect removed?

## Answer

Yes for the normal destroy→locker path. `Action_01130` listens for `EventCharacterDestroyed` (and also move-away, discard-from-play, dusk) and calls `setConditionEnded`, which:
1. removes `INDOMITABLE_WILL_CONDITION`
2. restores `CanBeClaimed` / `CanBecomeUncontrolled`
3. queues location uncontrolled

Destroy hub also recreates the character fresh (no memory), so the condition string is gone from the card instance either way — the important part of `setConditionEnded` for gameplay is the location flags + uncontrol.

## Caveat (not asked, note for later)

`Action_01130` does **not** listen for `EventCardSentToLocker`. Mid-day spend-to-locker paths (e.g. Action_03067) would not run `setConditionEnded` via that event. Dusk clears IW first (`EventDuskEndOfDay`), so Deal-with-the-Devil locker send is fine. Unclear if any mid-day locker send can hit an IW'd character in practice.
