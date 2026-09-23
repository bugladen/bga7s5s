# Technique_02021 — challenge-time null fatal on getDuelRoundOpponent

## Symptom
After choosing challenge target (`highDramaChallengeActionChooseTarget` → `stTechniqueAvailable`), server fatals:
`Attempt to read property "Id" on null` in `Theah::getDuelRoundOpponent` via `Technique_02021::isAvailableToPlayer`.

## WHY
Challenge technique availability runs **before** a duel exists. `getDuelRoundActor()` correctly returns null (no DUEL_ID/round). `getDuelRoundOpponent()` then dereferenced `$actor->Id` without a null check.

Technique_02021 also passed `$playerId` into `getDuelRoundOpponent()` which takes no args — leftover confusion; adversary is the duel opponent / CHOSEN_TARGET, not keyed by playerId.

## Fix (corrected after Eddie)
Eddie: "adversary" is in the **cost** (before •) → Technique is duel-only, cannot activate on challenge. Do **not** use CHOSEN_TARGET.

1. **Technique_02021**: Gate `!IN_DUEL` → false (same as 02023 / 01066). Then Influence compare vs `getDuelRoundOpponent()` with null check. No challenge path.
2. **Theah::getDuelRoundOpponent**: return null if actor is null — systemic guard (still useful; other callers shouldn't fatal).

First pass wrongly copied the 02026a challenge-adversary helper — that was wrong for cost-side "adversary".
