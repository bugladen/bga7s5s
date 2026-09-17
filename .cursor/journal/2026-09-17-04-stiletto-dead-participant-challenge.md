# Stiletto dead-participant challenge — correct ruling

## Soft-lock root cause (Accept Challenge with dead challenged)

`buildCity()` loaded discard piles but **not** `Locker-*`. After Stiletto destroys the challenged character, `getCharacterById(CHOSEN_TARGET)` returned null → `argsHighDramaChallengeActionAcceptChallenge` fatals on `$target->Location` → no Accept/Refuse/Intervene buttons.

Fix: load each player's locker in `buildCity` (same loop as discard). Also null-safe the Accept args with DB fallback.

### Unstick live table

1. Deploy this code to Studio
2. Challenged player hard-refreshes the table
3. Accept / Refuse / Intervene should appear
4. Accept or Refuse with challenged already dead → threat fizzles per ruling


## Matrix

| Scenario | Outcome |
|---|---|
| Challenged dead + Accept | Threat fizzles, no duel (absent adversary) |
| Challenged dead + Intervene | Duel with intervener at CHOSEN_LOCATION |
| Challenged dead + Refuse | Threat fizzles, no refuse wounds |
| Challenger dead + Accept/Intervene | Duel; threat from last-known challenger stats; defender plays round 1; then absent-adversary ends duel |
| Challenger dead + Refuse | Challenged takes refuse wounds; RH capped by last-known challenger stat |

Destroyed character + attachments already out of play → cannot react further to ChallengeIssued.

## WHY snapshot at ChallengeIssued

Destroy recreates the card with printed Modified* and empty attachments **before** GenerateThreat / refuse RH / round-1 `getDuelRoundOpponent`. Mid-duel last-known only exists via prior `actor_serialized` (round ≥ 2). Challenge-time full serialize into `CHALLENGE_LAST_KNOWN_CHALLENGER` / `_DEFENDER` is the only pre-duel source.

## Implementation notes

- Intervene lists/checks use `CHOSEN_LOCATION` (set in `stSetupChallenge` before Stiletto), not `$target->Location` (Locker).
- PlusOne/PlusTwoThrust null-safe on unequipped owner (`techniqueId` match is enough).
- Accepted + dead defender → `fizzled` → NEXT_PLAYER (do not start duel with dead round-1 actor).
- Cancel-on-death approach was wrong; do not reintroduce it.
