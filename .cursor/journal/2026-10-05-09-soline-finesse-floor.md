# Soline −1 Finesse floor vs Elena / Assassin's Garb

## Report
Soline `_01089` vs Elena `_03004` (base 0 FIN) + Assassin's Garb `_04006`.
At duel start Elena correctly showed 0. After Sorcery in dueling line (+1 Elena)
then wounding Soline (Garb +1) she showed **2 FIN**. Expected: Soline −1 cancels
Elena's +1 → stay 0 after Sorcery; Garb then brings her to **1**.

## Root cause
`EventHub` on `EventCharacterFinesseModifed`:
`ModifiedFinesse = max(0, $event->NewFinesse)`.

Soline's duel-start −1 against FIN=0 queues NewFinesse=−1 → clamped to 0.
Condition stamped, but **no stored reduction**. Later absolute-set +1 events
(Elena EndOfRound, Garb on wound) raise from the floored 0 without Soline
re-applying. Known footgun (Harpoon journal 2026-07-19, pattern-e) — previously
"mirror deliberately."

## Fix
`$FinessePenaltyAbsorbed` on Soline (and sibling Sango `_04043`):
- Apply: queue −1 only if ModifiedFinesse > 0 (Absorbed=true); else stamp
  condition with Absorbed=false
- Clear: +1 only if Absorbed (fixes end-of-duel overshoot on printed-0 too)
- `EventCharacterFinesseModifed` on affected character while !Absorbed and FIN>0:
  queue −1, Absorbed=true (re-absorb). Own re-apply already Absorbed → no loop
- Swaps: raise-then-lower (shared Absorbed flag — reverse order was wrong)

WHY Absorbed flag (not allow negative ModifiedFinesse): many consumers assume
FIN ≥ 0; changing the hub clamp is a wider regression surface. Absorbed matches
"passive stays active / reapplies when FIN changes" without rewriting the pipeline.

## Expected sequence after fix
1. Duel start: condition on Elena, Absorbed=false, FIN=0
2. Elena +1 Sorcery: FIN→1 → Soline re-absorbs −1 → FIN=0, Absorbed=true
3. Garb +1: FIN→1 (Absorbed already) → display 1

## Also
- pattern-a.md: Absorbed subsection under passive stat modifiers / Sango
- create-faction-attachment pattern-e: Soline/Sango fixed; Harpoon still open
- Harpoon `_03064` still has the clear-overshoot footgun if applied to 0-FIN

## Files
- `modules/php/cards/_7s5s/_01089.php`
- `modules/php/cards/bas/_04043.php`
- `.claude/skills/create-character/pattern-a.md`
- `.claude/skills/create-faction-attachment/pattern-e.md`

## Follow-up same day (2026-10-05-11)
Same Elena+Garb table: after duel FIN stuck elevated. Separate bug —
EventHub absolute `NewFinesse` write; multiple EventDuelEnd clears don't
compose. Hub now applies Old→New as a live delta. Absorbed still needed for
the floor case; delta hub does not replace it.
