# Comforting excess = shrink starting threat + rebuild

Eddie clarified: Maneuver should modify **original round-start threat** by the excess over the duel-stat, then recompute combat/Technique stats depending on when the Maneuver activated. Prior "Parry += excess" / `getCurrentDuelThreatExcludingCombatCard` was the wrong model.

## Model
1. `excess = max(0, starting_* − adversary Modified CHALLENGE_STAT)` — printed Influence-duel example; not actor ModifiedCombat.
2. `EventResolveManeuver` → `stackEvent(ThreatModified(-excess))` so it runs **before** the already-queued Calculate (queueEvent would land after and rebuild would miss the new starting).
3. `EventDuelCalculateManeuverValues` leaves R/P/T at 0 — EventHub `updateRoundWithCombatStats(maneuver, 0,0,0)` rebuilds `ending_*` from reduced `starting_*` + stored technique/combat/maneuver columns.
4. WHY rebuild: combat mode is **incremental** on ending; it will not re-apply itself when starting changes. Technique/maneuver modes already rebuild, but combat may already be in the columns.

## Cancel
Sticky `$ExcessDiscarded`. On cancel: restore starting only + `rebuildDuelRoundEndingThreats()`. Plain `ThreatModified(+excess)` wrong under combat Parry clamp (ending would not match f(restoredStart, RPT)).

## Helpers
- Added `getStartingDuelThreat`, `rebuildDuelRoundEndingThreats`
- Removed `getCurrentDuelThreatExcludingCombatCard` (superseded)

## Skill docs
pattern-c C.6 excess, checklist §37, helpers, references, SKILL shape table — all say starting+rebuild, not Parry.

## Feel
First fix was a local band-aid on the measurement base. Eddie's framing matches Leja's starting_* channel: baseline changes live in starting, ending is a projection of starting + R/P/T.

## Log (follow-up)
Resolve message now includes original starting threat and new starting (after excess discard): "Starting Threat goes from %d to %d."
