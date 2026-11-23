# Rena B-side technique missing on challenge

## Context
User: if Rena (05Gary) flips as part of a challenge action (e.g. Rage wound → Forced flip), she does not get her B-side Technique on that challenge.

## Diagnosis
`Technique_05GaryFlipped::isAvailableToPlayer` hard-requires `IN_DUEL`. Challenge TechniqueAvailable runs with `IN_DUEL=false`, so the technique is hidden even when she is already Wrathful (or just flipped mid-Rage). Dead `EventGenerateChallengeThreat` handler + Tomas `_04013` copy shape prove challenge was intended.

Even after unlocking availability, Resolve queues `createTechniqueTransitionEvent("05GaryFlipped")` which only exists under `DUEL_CHOOSE_TECHNIQUE_EVENTS` — no entry on `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`, so challenge activate would softlock / miss the destroy picker. Pattern E: dual hub + dual state class (04033 / 03013 shape).

Rage timing is fine: wound/flip queued at announce → CONFIRM_EVENTS drains before TECHNIQUE_AVAILABLE; same Id B-side is what getAvailableCharacterTechniques sees. Bug is availability + wiring, not flip order.

## Plan
1. Availability: 04001/04017 shape — actor==owner only when IN_DUEL; challenge uses performer host.
2. Add `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05GARYFLIPPED` (45505007), State class, hub transition, JS buttons.
3. Technique args/act accept both state ids.
4. Regression for challenge availability + threat + destroy on challenge state.

## Done
All four. Suite `Technique_05GaryFlipped_Test` green.

WHY heal still fires in challenge Resolve act (before Accept): destroy is cost left of •; heal is after • but lives in the same picker act as duel (Tomas destroy shape). Deferring heal to GENERATE_THREAT+ACCEPTED would be stricter rules-wise on Refuse; left as-is to match existing duel path and avoid a second deferred state. Revisit if Refuse-after-destroy-heal comes up.

Not fixing Tomas `_04013` the same way unless asked — same IN_DUEL-only bug + dead challenge threat handler, but different card.
