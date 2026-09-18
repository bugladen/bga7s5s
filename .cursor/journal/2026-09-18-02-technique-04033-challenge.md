# Technique_04033 challenge path (Iago)

## Context
Eddie: Technique is not duel-only — can fire during Challenge. Same class of bug as today's `Technique_04017` work.

## Bugs
1. **Availability** — `isAvailableToPlayer` always required `getDuelRoundActor()` → false on Challenge (no duel round) → Technique never offered.
2. **No challenge thrust** — only `EventDuelCalculateTechniqueValues` applied +1 Thrust. Challenge never queues Calculate → choosing Thrust did nothing to challenge threat.
3. **Picker hub** — Resolve queued transition `"04033"`, but that key only existed under `DUEL_CHOOSE_TECHNIQUE_EVENTS` / `DUEL_NEW_ROUND_EVENTS`. Mid-challenge the transition had nowhere safe to go (or would wrong-hub if we reused duel state).

## Fix / WHY
1. **Availability** — gate actor==owner only when `IN_DUEL` (04017 / 04021b). Challenge `getAvailableCharacterTechniques($performer)` already scopes to challenger.
2. **Adversary on Challenge** — do **not** call `getDuelRoundOpponent()`. Framework already sets `EventResolveTechnique` / `EventGenerateChallengeThreat` `adversaryId` from `CHOSEN_TARGET` (post-Intervene final defender). Threat goes onto that adversary via `adversaryThreat += 1`.
3. **+1 Thrust on Challenge** — `EventGenerateChallengeThreat` when `$UseThrust`. Parry choice → no challenge threat (Parry is duel-only). Choice must run on Resolve **before** Accept → GenerateThreat (01067 shape).
4. **Challenge picker state** — `"04033"` under `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS` → `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04033` → back to RESOLVE hub. Separate from duel choose state so `nextState` does not enter duel event hub (04017 lesson).

## Deferred threat unchanged
`$PendingThreatChoice` still arms on Resolve (challenge or duel). First adversary `EventDuelNewRound` after duel starts still prompts Add Threat / Pass. Challenge use → Accept → duel → adversary's first round is correct "next" round.

## Upload
`Technique_04033.php`, `States.php`, `states.inc.php`, `State_highDramaChallengeActionResolveTechnique_04033.php`, `OnUpdateActionButtons.bas.js`.

## Correction
Eddie: challenge resolve state belongs only in `State_highDramaChallengeActionResolveTechnique_04033.php` (GameState class), **not** in `states.7s5s.php`. Removed the duplicate array entry. Transition key `"04033"` stays in `states.inc.php` under RESOLVE_TECHNIQUE_EVENTS.

Smoke:
1. Iago Challenge → Technique offered → **Thrust-only button** (no Parry) → Accept → adversary threat includes +1 Technique.
2. Duel activation still offers Parry **and** Thrust.
3. Intervene after Thrust choice → GenerateThreat uses updated CHOSEN_TARGET as adversary.

## Challenge UI (Eddie)
Parry on Challenge is meaningless (no Calculate). Challenge picker shows only +1 Thrust; zombie applies Thrust (`actFromCardWithId(1)`). Duel choose state unchanged (both buttons).

