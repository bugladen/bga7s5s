# Syrneth Hand -2P overflow + Pavel +1P threat bug

## Report
Eddie: Strength of Ten (2R 1P 0T) under Syrneth Hand (-2P) + Boar's Guile (+1T), then Pavel Technique +1P. Expected final 2R 0P 1T → 4 threat mitigated by 2R only → 2 remaining. Log showed 1 remaining (as if Parry were 1). Sent threat 3 was correct.

## Root cause
`EventDuelCalculateCombatCardStats::removeParry()` floored at 0 after subtract. Hand on 1P: 1-2 → floored to 0, stored `combat_parry = 0`. Lost 1 point of penalty debt. Pavel's +1P on a separate `EventDuelCalculateTechniqueValues` then summed in technique mode: `0 + 1 = 1P`. Threat: 4 - 2R - 1P = 1.

July journal (`2026-07-17-02-thrust-threat-clamp.md`) kept the event-level floor because combat-mode `updateRoundWithCombatStats` did not floor negative R/P at apply (would *increase* threat). That was incomplete for Hand-style overshoot + later Technique/Maneuver Parry.

## Fix
1. `removeRiposte/Parry/Thrust`: always subtract (unless dashed); allow negatives. No `> 0` gate, no floor.
2. Combat branch of `DB::updateRoundWithCombatStats`: floor applied riposte/parry at 0 (match maneuver/technique). Store raw (possibly negative) in results/DB columns so later technique recalculation nets correctly.
3. EventHub already formats negative combat contributions in the duel log — no UI change.

## WHY this over Ren-style follow-through for Hand
Ren (_01121) already hops to Maneuver/Technique events with a once-per-round flag because its text is -1 and "less than 0 = 0". Hand is -2 on the combat-card event only; allowing combat_parry debt is the minimal fix so any later +Parry stacks against the full -2. Ren still skips when combat Parry <= 0 (does not create debt itself).

## Tests
`tests/regression/EventDuelCalculateCombatCardStats_Test.php` — debt preservation + Hand/Pavel net. Full `php tests/run.php` green.

## Unfinished
None for this bug. Watch: So It Begins / Burnished Cuirass / Mireli thrust remove now also create debt on 0-base cards — intended (full penalty vs later bonuses).
