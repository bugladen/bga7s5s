# Technique_04017 challenge adversary fix

## Context
Original `_04017` ship (2026-08-06) treated the Technique as duel-only: `isAvailableToPlayer` required `getDuelRoundActor()`, Resolve used `getDuelRoundOpponent()`, no `EventGenerateChallengeThreat`, no challenge picker state. Eddie: Technique can also fire during Challenge (before duel exists).

## Bug
`getDuelRoundOpponent()` / `getDuelRoundActor()` need an active duel round. During Challenge Resolve, `stHighDramaChallengeActionResolveTechnique` already sets `$event->adversaryId = CHOSEN_TARGET` and `$event->inDuel = false`. Using round helpers → null adversary → Academic/Hunter discard silently skipped (or availability false so Technique never offered).

## Fix / WHY
1. **Availability** — mirror `Technique_04021b`: only require actor==owner when `IN_DUEL`. Challenge uses `getAvailableCharacterTechniques($performer)` so host is already the challenger.
2. **Adversary** — `$event->theah->getCharacterById($event->adversaryId)` on Resolve. Same field for duel (`createResolveTechniqueEvent`) and challenge (`CHOSEN_TARGET`). Do **not** fall back to `getDuelRoundOpponent()`.
3. **+1 Thrust on Challenge** — `EventGenerateChallengeThreat` → `adversaryThreat += 1` (PlusOneThrust shape). Challenge never queues `EventDuelCalculateTechniqueValues`. Applies on Accept **and** Reject (refuse wounds) — same as other +Thrust techniques.
4. **Picker wiring (challenge)** — `"04017"` under `HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS` → state `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04017` (name kept for bas JS; returns to GENERATE_THREAT_EVENTS). Reusing `DUEL_CHOOSE_TECHNIQUE_04017` would nextState into duel event hub mid-challenge.

## Discard timing — Accept/Intervene only (Eddie correction)

**Wrong:** Queue discard on `EventResolveTechnique` during Challenge. Resolve runs before Accept/Refuse. GENERATE_THREAT also runs on Reject → if discard hung off Resolve or ungated GenerateThreat, Refuse would still discard.

**Right:**
- **Engage** still on `EventResolveTechnique` (always when Technique resolves).
- **In-duel discard** still on `EventResolveTechnique` when `$event->inDuel`.
- **Challenge discard** on `EventGenerateChallengeThreat` **only if** `CHALLENGE_ACCEPTED`. That flag is set by Accept and by Intervene (Intervene does **not** fire `EventChallengeAccepted`). Refuse leaves it false → no picker.
- Adversary at GenerateThreat = `CHOSEN_TARGET` post-Intervene (final defender).

Do not regress:
- Academic/Hunter stays effect gate, not availability.
- Attachment `sourceId` on `createTechniqueTransitionEvent`.
- Empty hand → notify + skip transition.

## State registration (Eddie 2026-09-18)

**Wrong:** Put `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_04017` only in `states.7s5s.php`. New-framework pickers (04033 challenge) live as `States/bas/State_*.php` GameState classes — machinestates-only entry does not register the interactive state properly → Accept skips picker straight into duel round 1.

**Right:** `State_highDramaChallengeActionResolveTechnique_04017` GameState class (mirror duel 04017 + 04033 challenge). Return hub = `GENERATE_THREAT_EVENTS` (post-Accept). Removed from `states.7s5s.php`. Transition `"04017"` stays on GENERATE_THREAT_EVENTS in `states.inc.php`.

Must upload: `State_highDramaChallengeActionResolveTechnique_04017.php`, `states.inc.php`, `Technique_04017.php`, bas JS. Do **not** rely on states.7s5s for this state.

Smoke:
1. Academic/Hunter + Technique on Challenge → Accept → discard picker → then Resolution/duel.
2. Same → Intervene → intervener discards.
3. Same → Refuse → engage + threat, no discard.
