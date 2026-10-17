# Blood Like Winter wiped the challenger (getCardObjectFromDb(0))

## Crash
Studio log 16-Oct, release 261015-0100. `stTechniqueAvailable` line 762 called `getCardObjectFromDb(0)`. Stack: `actPayForReaction` nextState("paid") → target-chosen events `stRunEvents` → `runEvents` endOfEvents → technique available.

That "paid" transition is `HIGH_DRAMA_CHALLENGE_ACTION_TARGET_CHOSEN_PAY_FOR_REACTION` → `TARGET_CHOSEN_EVENTS` → `endOfEvents` → `TECHNIQUE_AVAILABLE`. So a wealth reaction was paid while a challenge target had just been chosen.

## Why it was Blood Like Winter
Only reaction that writes `CHOSEN_PERFORMER = 0` is `Reaction_02048`, inside `EventRiskReactionTriggered`, which `actPayForReaction` queues before nextState("paid"). Justice Served Cold (`_02049`) is a targeting Risk and emits `EventCharacterTargeted` from `actHighDramaChallengeActionTargetChosen`, so BLW can offer in that window. Pressure itself is location-only (`pressureLocation(null)` and performerId 0 on the events). The global write was copied from flows that enter `stHighDramaPressureLocation`, which BLW does not. It destroyed the challenger the next state still needs.

## Fix
- Stop writing `CHOSEN_PERFORMER`.
- On pressure success, set `CHALLENGE_CANCELLED` when the held event is `ChallengeIssued`, or `CharacterTargeted` while `CHALLENGE_TYPE` is not normal. Normal stays alone so Come Hither does not poison the next challenge.
- `stTechniqueAvailable` bails to `challengeSkipped` (action resolved + "Challenge was cancelled") when that flag is set or the performer id is 0. Must be before `stIssueChallenge`, which clears the flag. A successful cancel that still issued the challenge would undo the pressure.

Failed pressure still re-emits the held event and now leaves the challenger in place, so the technique step can run.

## Tests
`Reaction_02048_Test`: pay no longer expects the global to become 0. New cases keep a pre-set challenger and set the cancel flag for JSC-type CharacterTargeted and for a held ChallengeIssued. 15 passed.
