# Croc de Lion (02026) — challenge adversary pull

## Context
Eddie already removed the `IN_DUEL` gate (37c02fcb) so both Duelist Techniques can be offered during Challenge. They still called `getDuelRoundOpponent()` for availability / args / act — that needs an active duel round actor. Outside duel, `getDuelRoundActor()` returns null and `getDuelRoundOpponent()` fatals on `$actor->Id`. Even if it returned null, availability would always be false on Challenge.

## Fix
1. **`getAdversary(Theah)` helper** on both `Technique_02026a` and `Technique_02026b`:
   - `IN_DUEL` → `getDuelRoundOpponent()`
   - else → `getCharacterById(CHOSEN_TARGET)`
   - WHY: same rule as 01193 / 04017 — Challenge Resolve sets adversary from `CHOSEN_TARGET` in `stHighDramaChallengeActionResolveTechnique`; never use duel-round helpers on challenge.

2. **Challenge choose states** (interactive attach-picker, like Bastien 01063 / Daniella 03013):
   - `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_02026a/b` (455020261 / 455020262)
   - Hub transitions `"02026a"` / `"02026b"` on `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`
   - State classes under `States/tac/` mirroring duel choosers but returning to challenge resolve events
   - JS button handlers for the challenge state names (same attachment buttons as duel)

WHY challenge states were required: Resolve always queues `createTechniqueTransitionEvent(..., "02026a"|"02026b")`. Duel hub already had those transitions; challenge hub did not — activating on Challenge would hit an unknown transition.

## Follow-up: defer engage/destroy until Accept (not Refuse)

Eddie: effects must not occur if the challenged player refuses.

Challenge Resolve runs **before** Accept/Refuse. Queuing the attachment chooser from `EventResolveTechnique` applied engage/destroy even when the challenge was later refused.

**Fix (04017 / Jägerarmbrust shape):**
1. `EventResolveTechnique` — queue chooser **only if `$event->inDuel`**.
2. `EventGenerateChallengeThreat` — queue chooser **only if `CHALLENGE_ACCEPTED`**. WHY: GENERATE_THREAT also runs on Refuse (wound threat); Intervene sets `CHALLENGE_ACCEPTED` without `EventChallengeAccepted`.
3. Hub route `"02026a"`/`"02026b"` moved from `RESOLVE_TECHNIQUE_EVENTS` → `GENERATE_THREAT_EVENTS`.
4. Challenge choose states return to `GENERATE_THREAT_EVENTS` (not resolve events).

Re-check valid attachments when queuing from GenerateThreat (Intervene can change `CHOSEN_TARGET`).

## Still out of scope
- 03039 / 02037 sibling challenge-adversary issues unless asked.
