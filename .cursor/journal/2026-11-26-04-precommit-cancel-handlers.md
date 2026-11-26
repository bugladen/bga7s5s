# Pre-commit EventManeuverCanceled / EventTechniqueCanceled fixes

Commit aborted by hook on 8 files that got touched in the Patricia queryCard sweep (or were already staged) without cancel coverage.

## Fixes

Real cancel cleanup (had deferred state):
- `Maneuver_01031` — clear `IsActive` / `DuelLocation`
- `Technique_01067` — clear `UseRiposteInstead`

Comment exempt (no internal state; resolve queues effect/transition only):
- `Maneuver_01108a`, `01108b`, `01133`, `02009`
- `Technique_01010`, `02003`

WHY real handlers on 01031/01067: cancel normally deletes Resolve before it fires, but other stateful maneuvers (01129, 01135, 04030) still reset flags defensively so a sticky IsActive/UseRiposteInstead can't leak into a later duel if ordering changes.

User still needs to re-stage and retry commit — I did not commit.
