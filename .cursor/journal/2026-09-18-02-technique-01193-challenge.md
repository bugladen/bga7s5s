# Technique_01193 Burnished Cuirass — challenge arming

## Context
Eddie: Technique can be used during Challenge (IN_DUEL gate already removed this morning in 8a35a5b4). Ensure adversary is correctly pulled on Challenge, and that the deferred thrust reduction still applies.

## Bug
`EventDuelNewRound` cleared `ReduceAdversaryThrust` when the owning character became actor. After Challenge activation that NewRound is **round 1 of the duel for the challenger** — before the adversary's first combat card. Flag died; `-1 Thrust` never applied.

Also: Resolve never stored the challenge target. Relied on `owner == event.adversaryId` at combat-card calc time, which works in-duel but is fragile vs capturing Resolve's adversaryId (set from CHOSEN_TARGET in `stHighDramaChallengeActionResolveTechnique`).

## Fix
1. Store `$event->adversaryId` on Resolve (fallback `CHOSEN_TARGET`) — same "no getDuelOpponentId on challenge" rule as 04017/04033 on bas.
2. Apply `removeThrust(1)` when `event.actorId == stored AdversaryId`.
3. **Removed** owner-actor NewRound clear (Maneuver_01084 pattern: clear on apply / cancel / duel end only).
4. Clear on `EventChallengeRejected` — Resolve runs before Accept/Reject; refuse must not leak the flag into a later duel.

## Not done / WHY
No `EventGenerateChallengeThreat` handler. Card text is deferred `-1 Thrust` on the adversary's **combat card**, not `+Thrust` at activation. Challenge threat from this technique is correctly 0 (Character still adds Combat/etc. via GenerateThreat). If Eddie meant something else by "challenge thrust is being generated," clarify — do not invent +1 Threat.

## Sibling risk
`Technique_01204` (Syrneth Hand) has the same NewRound premature-clear pattern. `Technique_02037` / `Technique_01186` similar. Out of scope unless asked.
