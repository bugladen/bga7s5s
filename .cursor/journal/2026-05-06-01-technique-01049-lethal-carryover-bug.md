# Technique_01049 Lethal Carrying Forward Across Rounds

## The Bug

Eddie reported that when Technique_01049's "Gain Lethal" fires, the lethal flag persists into every subsequent round of the duel — not just the round it was activated in.

## Root Cause — and Why The First Fix Was Wrong

My first attempt cleared both lethal flags in `stDuelNewRound` for round 2+. Eddie corrected me: **the lethal flag DOES need to carry forward between rounds — it just has to be cleared once consumed.**

WHY this matters (the design I missed on the first read):

- `stDuelEndOfRound` only consumes one side's leftover threat — the actor's. It zeros `ending_<actor>_threat` (line 1466 before fix). The adversary's leftover `ending_<adversary>_threat` carries into the next round as that round's starting threat.
- `Technique_01049`'s `createGainLethalEvent` (in `EventFactory.php:1222`) sets the **adversary's** lethal flag, not the actor's:
  ```
  $challengerThreatIsLethal = $actorId == $challengerId ? null : true;
  $defenderThreatIsLethal  = $actorId == $defenderId  ? null : true;
  ```
  i.e. when the actor (challenger) plays it, it sets `defender_threat_is_lethal`. That makes sense given the carry-forward design: the adversary's threat is the one that survives this round and hits next round, where the adversary will be the new actor. Setting their lethal flag makes their leftover-threat-into-wounds calculation lethal NEXT round.
- So `stDuelNewRound` reading `challenger_threat_is_lethal`/`defender_threat_is_lethal` from the previous row is intentional — it's the same row that carries `ending_*_threat` forward, and the lethal flag has to ride along.

The actual bug: `stDuelEndOfRound` zeros the actor's leftover threat but NOT the actor's lethal flag. So once a round had any side flagged lethal, that flag stayed pinned to 1 on every future row that ever read forward through it.

## The Fix

In `stDuelEndOfRound` (`StatesTrait.php`), clear the actor's lethal flag in the same `UPDATE` that zeros the actor's leftover threat:

```php
$lethalField = $actorId == $challengerId ? "challenger_threat_is_lethal" : "defender_threat_is_lethal";
$sql = "UPDATE duel_round SET $field = 0, $lethalField = 0 WHERE duel_id = $duelId AND round = $round";
```

`stDuelNewRound` is reverted to its original behavior — it correctly carries both ending threats AND lethal flags forward from the previous row.

### How This Plays Out Across Rounds

Walking through a 01049 in round 1 with actor=challenger:
- **Round 1 end:** challenger's threat consumed → wounds defender. Challenger's lethal cleared (was 0 anyway). Defender's lethal stays 1, defender's leftover threat carries forward.
- **Round 2 start:** `stDuelNewRound` reads forward → starts round 2 with defender side lethal=1 and ending defender threat as starting threat.
- **Round 2 end:** defender is now the actor. Defender's leftover threat consumed → wounds challenger, applied as lethal (bypasses stat cap). Defender's lethal cleared.
- **Round 3 start:** both sides' lethal flags = 0. Done.

That's the intended one-shot semantics: 01049's lethal applies to the adversary's leftover threat being consumed once, then disappears.

## Adjacent Note — `DEFENDER_THREAT_IS_LETHAL` Global

The global `Game::DEFENDER_THREAT_IS_LETHAL` is set by `EventGenerateChallengeThreat` (EventHub.php:1391) and read by round 1 of `stDuelNewRound`. Didn't trace whether it's cleaned up between duels — could be a separate latent bug if a duel ends and another starts within the same game. Out of scope for this fix.

## Files Changed

- `modules/php/StatesTrait.php`:
  - `stDuelEndOfRound()` — clears the actor-side lethal flag along with the actor-side ending threat
  - `stDuelNewRound()` — unchanged from original (continues to carry both ending threats and lethal flags forward)
