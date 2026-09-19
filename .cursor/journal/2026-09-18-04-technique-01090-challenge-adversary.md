# Technique_01090 Lorenzo — challenge adversary pull

## Context
Eddie: Technique can be used during Challenge (not always in-duel). Ensure adversary id is correctly pulled. Same family as today's 01193 / 02026 work.

## Bug
`EventResolveTechnique` called `getDuelRoundActor()` / `getDuelRoundOpponent()`. Outside duel those need an active duel-round actor — null actor → fatal on `$actor->Id`. Challenge Resolve sets `adversaryId` from `CHOSEN_TARGET` in `stHighDramaChallengeActionResolveTechnique`.

Also: Resolve always queued transition `"01090"` (acknowledge UI). Challenge hub had no `"01090"` route — would fail even after fixing the adversary pull.

## Fix
1. **`getAdversary` / `getActor` helpers** — prefer event ids, else IN_DUEL → duel-round helpers, else `CHOSEN_TARGET` / `CHOSEN_PERFORMER`.
2. **Defer reveal on Challenge** to `EventGenerateChallengeThreat` + `CHALLENGE_ACCEPTED` (02026 / 04017 shape). WHY: Resolve runs before Accept/Refuse; peeking the deck on Refuse is wrong. GENERATE_THREAT also runs on Refuse for wound threat — gate on `CHALLENGE_ACCEPTED` so Intervene still works.
3. **Challenge acknowledge state** `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01090` (45501090), hub route on `GENERATE_THREAT_EVENTS`, returns there. JS enter/leave/buttons mirror duel acknowledge.
4. **`getArgs` uses `CardPlayerId`** for opponentName — not `getDuelRoundOpponent()`. WHY: on NewRound the actor IS the adversary (opponent would be Lorenzo); on Challenge there is no duel opponent. CardPlayerId is the deck owner captured at reveal.

## Unchanged
- Deferred "play revealed card or take wound" still fires from `EventDuelNewRound` via `CardPlayerId` — that path is duel-only by definition.
- Transition name `"01090"` is shared; hub context (choose-technique / new-round / generate-threat) picks the right state.

## Sibling risk
03039 still called out in 02026 journal as out-of-scope unless asked.
