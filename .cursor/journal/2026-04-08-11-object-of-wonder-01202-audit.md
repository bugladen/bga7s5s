# Object of Wonder (01202) Audit

## Card Text
> Finesse +1, Influence +1, Wealth Cost 2
> Traits: Artifact, Syrneth, Unique
> **Forced:** When equipping this card • It always equips to your Leader. (Regardless of who equipped it.)
> **Reaction:** When your non-Mercenary character is sent to The Locker • Put them into your Approach Deck and send this card to The Locker instead.

## Bug: Duel double-trigger — `setUsed` not called when player commits to saving

When a character is destroyed during a duel and the player chooses "saveCharacter", the code deferred the actual save to `EventDuelEnd` via the `WaitAfterDuel` flag. But it didn't call `setUsed()` — only `saveCharacter()` did that, which runs later at duel end.

WHY this matters: Between the player's commit and the duel ending, `isAvailable()` still returns true (`Used == false`). If a second character is destroyed during the same duel, `handleEvent` triggers again, overwrites `SavedCharacterId`, and queues another reaction transition. The player sees a second save prompt, but the first character's save is silently lost — `saveCharacter` only runs once at `EventDuelEnd` with the last `SavedCharacterId`.

Two-part fix:
1. Added `$this->setUsed($game->theah, true)` at the top of the `saveCharacter` branch in `performReaction`, before checking `$inDuel`. This marks the reaction used immediately when the player commits, preventing double-trigger.
2. Removed `$this->isAvailable()` from the `EventDuelEnd` handler guard. Since `setUsed` is now called early, `isAvailable()` returns false by duel end — the old guard would prevent `saveCharacter` from ever running. `WaitAfterDuel` alone is sufficient since it's only set when the player committed.

The `setUsed` call inside `saveCharacter` (line 95) is now redundant but harmless — defense in depth.

WHY `setUsed` before `$inDuel` instead of inside the duel branch: Both duel and non-duel paths need it. Non-duel calls `saveCharacter` which also calls `setUsed`, making it redundant there, but the early call is cleaner than having it only in the duel branch. Consistent regardless of path.

## Everything else checked out
- **Forced ability:** `getRequiredAttachTargetId` redirects to controlling player's Leader ✓. Called from `FrameworkActionsTrait.php` line 672 before creating the equip event. "Regardless of who equipped it" means regardless of which of your characters equips — the performer's controller's Leader is always the target.
- **Reaction trigger:** `EventCharacterDestroyed` is the canonical "sent to the locker" event for non-Brute characters. Cards handle it before EventHub (due to `runEventHubAfterCards = true`), so the reaction queues before the character actually moves to locker. By the time the player responds, EventHub has moved the character.
- **Guard checks:** same controller ✓, not Leader ✓ (defensive — Leaders' attachments unequip before destroy event, so `ownerIsAttached` would be false anyway), not Mercenary ✓
- **Save flow:** remove from locker → approach deck → unequip attachment → send attachment to locker → events queued. Order is correct.
- **Pass case:** No side effects, `SavedCharacterId` left stale but safely overwritten on next trigger.
- **Stats:** Finesse +1, Influence +1, WealthCost 2, Traits Artifact/Syrneth/Unique ✓

## Note on Leader exclusion
Card text says "non-Mercenary" but code also checks `! $dyingCharacter instanceof Leader`. This is practically unreachable: Object of Wonder equips to the Leader, so if the Leader is destroyed, their attachments are unequipped first (via `unEquipAllAttachments` in the `EventCharacterWounded` handler), making `ownerIsAttached` false before `EventCharacterDestroyed` fires. The check is defensive and correct to keep.

## Files Changed
- `modules/php/cards/_7s5s/reactions/Reaction_01202.php` — added early `setUsed` in `performReaction`, removed `isAvailable()` from `EventDuelEnd` guard
