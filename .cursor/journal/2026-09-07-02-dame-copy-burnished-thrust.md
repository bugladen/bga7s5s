# Dame copies Burnished Cuirass — -1 Thrust never applied

## Bug
Kaspar (`_03014`) had Burnished Cuirass (`_01193`) + Dame of Swords (`_02055`) equipped. Dame copied Cuirass's technique. Adversary's next combat card did not get -1 Thrust.

## Root cause
`Technique_02055` clones the source technique onto the **participant Character** (`setOwnerId($actor->Id)`, `IsTemporaryCopy = true`).

`Technique_01193` gated the deferred -1 on:

```php
$isAttached = $attachment instanceof Attachment && $attachment->isAttached();
```

Character-owned copies fail that check → flag `ReduceAdversaryThrust` can be set on resolve, but `EventDuelCalculateCombatCardStats` never applies the penalty.

Same gate lived on Syrneth Hand `Technique_01204` (-2 Parry next round) — same copy path would no-op there too. Fixed prophylactically.

## Not the temporary-copy cleanup
Base `Technique` removes `IsTemporaryCopy` on `EventDuelNewRound` only when `owner.ControllerId == event.playerId`. Adversary's round ≠ Kaspar's controller → copy survives through combat-card calc. Timing was fine; the Attachment gate was the miss.

## Fix
- `ownerCanApplyDeferredEffect()`: Attachment → still requires `isAttached()`; Character → allow (copy from 02055/01165).
- `markOwnerUpdated()` always dirties owning card (was Attachment-only on resolve) so the flag persists on Character hosts.

## Do not regress
- Unequipped Cuirass mid-duel: Attachment + !isAttached still blocks (original intent).
- Temporary copy still self-removes on owner's next `EventDuelNewRound` / duel end.

## Files
- `modules/php/cards/_7s5s/techniques/Technique_01193.php`
- `modules/php/cards/_7s5s/techniques/Technique_01204.php`

---

## Follow-up: Kaspar Technique_03014 missing from Dame picker

Eddie: Dame copies effects; it should not care about source cost/prereqs. Without Eisenfaust, `Technique_03014` never appeared.

**Cause:** `getAvailableTechniques` filtered with `$t->isAvailableToPlayer()`. Kaspar's technique gates on `hasEisenfaust` there — effect resolve does not re-check Eisenfaust, so once copied it would wound fine; the picker was the only block.

**Fix:** Stop using `isAvailableToPlayer` for Dame's list. Skip self / ClassId `Technique_02055` / `IsTemporaryCopy` only — same shape as Yepikhodov `Technique_03051`.

WHY not change Technique_03014: Eisenfaust is correct for *playing* Kaspar's technique; Dame is the wrong place to enforce it.

## Files (picker)
- `modules/php/cards/tac/techniques/Technique_02055.php`
