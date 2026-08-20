# Leja Juska (01203) — +1 Threat wiped when Maneuver played

## Report
Reaction_01203 adds/removes threat via `createThreatModifiedEvent`. The +1 shows up, then disappears when a Maneuver is activated.

## Root cause
Not EventDuelCalculateManeuverValues itself — the wipe is in `DB::updateRoundWithCombatStats` for `mode == 'maneuver'` (same for `technique`):

```
ending = starting_*_threat
then re-apply all stored R/P/T + event contribution
```

`updateRoundThreats` (EventThreatModified handler) only mutated `ending_*_threat`. So Leja's delta lived only in ending. First maneuver calc rebuilt ending from untouched starting → Leja's threat gone.

This is the same baseline-vs-recalc tension that made Raise the Stakes (02039) move to PENDING_* globals for end-of-round adds. Leja fires at round start before R/P/T, so baking into starting_* is the right channel.

## Fix
`DB::updateRoundThreats` now applies the same deltas to `starting_*` and `ending_*`.

WHY both: maneuver/technique paths treat starting as the immutable-for-the-calc baseline. Any ThreatModified that should survive a later recalc must update starting. Ending alone is enough only until the next rebuild.

Also: EventHub notify + `notif_updateRoundThreats` now refresh starting chips so the UI matches (previously only ending chips moved; starting column stayed stale until reload).

## Deliberately not changed
- Reaction_01203 itself — event args were already correct; framework was wrong.
- Porté (01085) zeroing via `-getCurrentDuelThreat`: still imperfect if a *second* maneuver/technique recalc runs after Porté in the same round (re-applies stored R/P/T). Pre-existing; updating starting helps some cases, not all. Separate bug if it surfaces.

## Feel
April audit of 01203 checked the reaction wiring and missed this because the failure mode is one layer down in DB combat-stat rebuild. Classic "event looks right, persistence model disagrees."
