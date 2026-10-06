# Soline −1 Finesse stuck after duel end

## Report
Characters at Soline Leader `_01089` location still showed −1 FIN after the duel
ended. Victim had been in the duel with Soline's aura applied.

## Context
Yesterday (`2026-10-05-09`) added `$FinessePenaltyAbsorbed` so floor (FIN=0)
apply does not queue a fake −1, and clear only +1 when Absorbed. That fixed
Elena/Garb mid-duel display. `2026-10-05-11` made EventHub apply Finesse as
live deltas so multi-source EventDuelEnd clears compose.

## Root cause
`raiseFinesse` restored +1 **only** when `$this->FinessePenaltyAbsorbed` on
Soline. Absorbed lives on Soline's card instance; the victim carries the
condition + the actual ModifiedFinesse reduction.

If Absorbed is false while the victim still has the condition and a live
reduction (serialize default after mid-duel deploy of the new bool property;
instance fields wiped; any desync), EventDuelEnd:
1. Sees condition → enters raise
2. Skips +1 (Absorbed false)
3. Removes condition
4. FIN stays permanently reduced

Floor case (never reduced, FIN still 0) correctly skips +1 — indistinguishable
from desync if we only look at Absorbed.

## Fix
1. **`raiseFinesse` restore when `Absorbed || ModifiedFinesse > 0`**
   - Absorbed true → normal undo
   - FIN>0 with condition still on → treat as live reduction (desync), restore
   - FIN=0 and !Absorbed → pure floor leftover, skip +1 (no overshoot)
2. Same heuristic on Sango `_04043` `raiseAdversaryFinesse` (same Absorbed shape)
3. **`_01089::clearLeftoverDebuffs` from `stDuelEnd`** (Sango sibling) — scan for
   leftover SOLINE condition, raise, persist Soline field clears to DB (stDuelEnd
   does not runEvents; IsUpdated alone would die across the next request)

WHY heuristic not "always +1": floor-at-0 overshoot was the Absorbed reason.
WHY FIN>0 gate: desync with a stored −1 always leaves FIN≥1 after a successful
apply (hub clamps at 0; apply skips queue when already 0).

## Files
- `modules/php/cards/_7s5s/_01089.php`
- `modules/php/cards/bas/_04043.php`
- `modules/php/StatesTrait.php` (import + stDuelEnd call)

## Follow-up (same day)
`clearLeftoverDebuffs` fatal: `$theah->cards` is **private**. Switched to
`$theah->getAllCards()` — same access pattern as `getWorldCards` / Indomitable Will.
