# Challenge TechniqueActivated → empty duel_round SQL

## Symptom

Selecting `Technique_PlusOneThrust` on Langschwert (`_01048`) during `highDramaChallengeActionActivateTechnique` fatals:

`SELECT actor_id FROM duel_round where duel_id = AND round =`

## Root cause

Not Langschwert itself — `Technique_PlusOneThrust` is fine (challenge threat via `EventGenerateChallengeThreat`).

`Reaction_03013a` (Daniella Continuous Sorcerer grant) listens to **every** `EventTechniqueActivated`. On activate it called `getDuelRoundActor()` before the challenge-time `ownerId` fallback. Challenge flow has no `DUEL_ID` / `DUEL_ROUND` yet → empty SQL.

Reproduced whenever Daniella is in the city and any challenge technique is chosen (Langschwert was the reporter's case). Click queues TechniqueActivated → `stRunEvents` → Reaction_03013a handleEvent → boom; stays on activate-technique state after rollback.

Journal `2026-09-05-01` already documented the challenge ownerId path ("no duel round actor yet") but the duel-actor call ran first without an `IN_DUEL` gate.

## Fix

1. `Reaction_03013a::maneuverOrTechniqueWithOwnerAsActor` — only call `getDuelRoundActor` when `IN_DUEL`; challenge still uses `ownerId === Daniella`.
2. `Theah::getDuelRoundActor` — return null if duel id/round unset (matches `?Character`; stops the same class of SQL fatal elsewhere).

## Do not regress

- Mid-duel: Daniella-as-actor still triggers via getDuelRoundActor.
- Challenge: Daniella's own technique (ownerId = her card) still triggers via ownerId path.
- Attachment techniques on challenge stamp ownerId as the **attachment** (FrameworkActionsTrait asymmetry vs duel which stamps actor) — Reaction still won't fire for those; that predates this fix.
