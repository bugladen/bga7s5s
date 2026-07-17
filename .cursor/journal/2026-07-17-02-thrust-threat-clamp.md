# Thrust threat clamp in updateRoundWithCombatStats

## Context

Eddie asked whether threat from EventDuelCalculateTechniqueValues could go negative. Answer: actor threat no (riposte/parry clamped), opponent threat yes via unclamped negative thrust. Then: "Clamp thrust like the others."

## What changed

In `DB::updateRoundWithCombatStats`, all three modes (maneuver, technique, combat/else) now clamp thrust before applying:

```php
$thrust = $totalThrust; // or $eventThrust in combat branch
if ($opponentThreat + $thrust < 0)
    $thrust = -$opponentThreat;
$opponentThreat += $thrust;
```

## WHY this shape (not floor thrust at 0)

Riposte/parry do `if ($x < 0) $x = 0` — that would kill intentional negative thrust from Technique_01050, Technique_02002, Technique_03039, Maneuver_03009, etc.

So we clamp the *application* so opponent ending threat cannot go below 0, while still allowing negative thrust values to reduce threat down to zero. Stored `results['thrust']` / DB column still records the event's raw thrust contribution (uncapped) — same as riposte/parry results storing the event value, not the clamped applied amount.

## Applied to all three modes

WHY not just technique: same unclamped `+= $totalThrust` / `+= $eventThrust` existed in maneuver and combat branches too. Same bug class.

## Unfinished / watch

Combat branch still doesn't floor negative riposte/parry to 0 (maneuver/technique do). Pre-existing asymmetry; left alone.

## Follow-up: EventDuelCalculateManeuverValues

Eddie asked the same question for maneuver. Same answer as technique after the clamp:

- Actor threat: no — riposte/parry floored at 0 and capped by current actor threat
- Opponent threat: no longer — thrust application clamped in maneuver branch of `updateRoundWithCombatStats`
- Event fields can still hold negative thrust (Maneuver_03009 does `$event->thrust -= 1`); that's intentional and still stored raw

Before today's clamp, opponent threat *could* go negative via that path.

## Follow-up: removeRiposte/Parry/Thrust clamps on EventDuelCalculateCombatCardStats

Eddie manually added floor-at-0 after subtract in the three remove* methods. Asked if needed.

**Yes, keep them** — not for the threat-going-negative question, but for combat-stat semantics.

- Callers: Ren (_01121), _01183, Reaction_02017, Technique_01204 (removeParry 2), Technique_01193 (removeThrust 1), Maneuver_01135 (removeThrust 2).
- Ren's card text literally: "Anything less than 0 is treated as a 0."
- `if ($stat > 0)` before subtract does NOT prevent overshoot: Parry 1 + removeParry(2) → -1 without the floor.
- Combat `updateRoundWithCombatStats` else-branch still does NOT floor negative riposte/parry at apply time (unlike maneuver/technique). Negative riposte/parry would `threat -= (-n)` and *increase* actor threat — wrong. Event-level floor is what stops that.
- Thrust overshoot would incorrectly reduce opponent threat (DB thrust clamp only stops going below 0, not the bogus reduction from a 0-thrust card).

WHY not redundant with DB threat clamps: different layer. DB clamps ending threat; remove* clamps the combat card R/P/T contribution itself.
