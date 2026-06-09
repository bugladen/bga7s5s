# Audit + Fix: stPlunderCheckFifthDayVictory

`StatesTrait.php:1846`. Game-type state that runs on every day-end; on day 5 resolves the Renown victory and its four-tier tiebreak (renown → controlled locations → influence → leader wounds), populating `aux_score` along the way.

## What I found

### 1. Double `nextState` on day 5 (the bad one)

Earlier branches end with `nextState("endOfGame"); return;`. The wounds branch was missing the `return`. Then the trailing `nextState("next")` at the very end of the method was OUTSIDE the `if ($day == 5)` block, so every day-5 path that reached the wounds tier hit `nextState` twice. BGA throws on that.

The trailing `nextState("next")` only existed to handle day != 5. The fix is to `return` after the day-5 endOfGame transition so the trailing call is reached only for non-day-5 ticks.

### 2. Destroyed-leader override applied AFTER aux score

```php
$auxScore += 20 - $leader->Wounds;          // 0 wounds → +20 (best!)
...
if ($this->characterIsInDiscardOrLocker($leader))
    $leader->Wounds = 100;                   // local tiebreak only
```

A destroyed leader (wounds 0 at the moment they were discarded) was getting the BEST possible aux-score contribution while simultaneously losing the in-loop comparison. Two scoring systems disagreeing — silent bug, would only surface as confused players seeing a destroyed leader take the win on tiebreaker aux.

WHY this is easy to miss: the destroyed character has `Wounds=0` because wounds get cleared on the way to the discard pile, not because they took zero damage. Without the discard-or-locker check, you score them as untouched.

### 3. Mutating `Theah` leader object

`$leader->Wounds = 100;` mutated the cached character — anything else in the same request that read that leader would see 100. Probably benign in practice (we're at end-of-game) but a footgun.

Combined fix for #2 + #3: compute `$effectiveWounds = isDestroyed ? 100 : $leader->Wounds` ONCE, store in a local `$effectiveWounds[$playerId]` map, and use that everywhere (notify, aux, comparison, tie check).

### 4. Silent wounds-tier tie

All three earlier tiers emit a "still tied → next tier" notification when they fail. The wounds tier only notified on a clean win. If leaders also tied on wounds the game ended with no on-screen explanation. Added an `else` notification.

## What I didn't fix

- **Double iteration** to find max-then-ties (renown, locations, influence, wounds). Each tier walks the candidate list twice and re-reads the same value. Cleaner as a single pass building `[playerId => value]` then max+filter. Left alone — it's not buggy and the rewrite would touch a lot of lines in code that's been stable for years. Worth doing if this method comes up again for other reasons.
- **`$highestReknown = -1` start** — works because reknown is non-negative. Not worth churning.
- The "reknown" misspelling. Pervasive; not my fight today.

## Not bugs (checked and dismissed)

- `getPlayerNameById` is used throughout (matches memory rule).
- `characterIsInDiscardOrLocker` is used (matches memory rule, not the deprecated `ControllerId == 0`).
- Method signature is typed (`: void`).
- The "no locations controlled" fallback at line 1964 correctly carries `reknownWinners` forward.

## Loose end

The wounds-tier tie now shows a "shared victory" message and exits to `endOfGame`. BGA will rank by aux_score, which the four tiers all contributed to. That's the right behavior — no further deterministic tiebreak exists in the rules I can find, and the aux_score already encodes the tier weights (1000 × locations + 100 × influence + (20 - wounds)). If a future ruling adds a fifth tier, hook it in BEFORE the `endOfGame` exit.
