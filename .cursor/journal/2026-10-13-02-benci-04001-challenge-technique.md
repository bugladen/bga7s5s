# Benci Technique_04001 not offered on Challenge

## Symptom
Eddie: Benci was not prompted to activate his card Technique when issuing a challenge.

## Cause
`Technique_04001::isAvailableToPlayer` hard-required `IN_DUEL` + duel round actor. Challenge TechniqueAvailable has no duel round → always false → never listed.

Original implement treated it as duel-only (Andare journal even cited "duel-only like Benci"). That was wrong — printed italic says techniques do not resolve if the challenge is refused, which only makes sense if Challenge activation is legal.

Same class of bug as Technique_04017 / 04033 (fixed earlier).

## Fix / WHY
1. **Availability** — only gate actor==owner when `IN_DUEL` (04017/04033). Challenge performer already scoped by `getAvailableCharacterTechniques`. Keep empty-deck hide.
2. **Defer look until Accept** — Challenge `EventResolveTechnique` is pre-Accept/Refuse. Peeking there would violate the refuse reminder. Defer look/sink/reorder to `EventGenerateChallengeThreat` + `CHALLENGE_ACCEPTED` + `!preview` (01090 / 04017). Duel still runs on Resolve.
3. **Challenge picker states** — `"04001"` under `GENERATE_THREAT_EVENTS` → sink + reorder GameState classes that return to GENERATE_THREAT_EVENTS hub. Do **not** put under RESOLVE_TECHNIQUE_EVENTS (pre-Accept). Duel states unchanged; same transition key is dispatcher-scoped.
4. **JS** — mirror duel chooseList / EventHandlers / buttons for the two challenge state names.

## Do not regress
- Refuse after choosing Technique → no look, no sink.
- Accept/Intervene → look after Accept, before duel start finishes.
- Mid-duel activation still works via DUEL_CHOOSE_TECHNIQUE_EVENTS.
- Empty deck between choose and Accept → startLookOrSkip no-ops.

## Skill debt
`create-character/pattern-e.md` still documents Availability as `IN_DUEL` only for this shape — wrong now. Update when next touching that skill.
