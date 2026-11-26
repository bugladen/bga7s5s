# Syrneth Hand — intervene left AdversaryId on original target

## Bug (Eddie report)
Challenge vs Prima Rosa with Syrneth Hand Technique activated+resolved. Eko
intervened to Accept. Opponent played Hidden Corridors (printed Parry 1) as
Eko's combat card — still Parry 1. Should have been Parry 0 (-2 from Technique).

## Root cause
`Technique_01204` stores `AdversaryId` at `EventResolveTechnique` (challenge
path: original CHOSEN_TARGET = Prima Rosa). Apply gates on
`event.actorId == AdversaryId`. Intervene replaces the defender via
`EventCharacterIntervened` + updates CHOSEN_TARGET, but never fires
`EventChallengeAccepted`, and 01204 never listened to Intervened — so
`AdversaryId` stayed Prima Rosa. Eko's card never matched → no -2 Parry.

Same shape as Action_05Hulk (Rage) which already retargets
`FirstCombatParryCharacterId` on Intervened.

## Fix
On `EventCharacterIntervened`, if armed and `AdversaryId == oldTargetId`, set
`AdversaryId = newTargetId`. Twin `Technique_01193` (Burnished Cuirass -1 Thrust)
got the same retarget — identical Resolve/apply pattern from the 2026-09-19
challenge arming fix.

## Tests
`Technique_01204_Test` / `Technique_01193_Test`: intervene retargets + applies
to intervener card; 01204 also asserts unrelated intervene does not retarget.

## Follow-up (not fixed this pass)
Same stale-AdversaryId-on-intervene risk likely on challenge-armed siblings that
store AdversaryId at Resolve and match actorId later: `Technique_01186`,
`Technique_02017`, `Technique_02037`, possibly `Technique_01096`. Fix when
reported or when touching those files — same Hulk/01204 pattern.
