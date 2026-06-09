# Audit + fix: stPlanningPhaseDetermineFirstPlayer

Located in `modules/php/StatesTrait.php`. Runs as the `action` for state `PLANNING_PHASE_DETERMINE_FIRST_PLAYER` after `dayPlanned`.

## Bugs found

1. **`tiedInitiative` never cleared.** Loop only flipped the flag true on equal-initiative match and on higher-initiative match it updated `$highInitiative` / `$highPlayerId` but left a previously-set tie flag in place. Order-sensitive: e.g. `[3, 3, 5]` iterated in that order produced a solo winner (5) but still entered the tiebreak branch.

2. **`$highInitiative = 0` sentinel collision** with real `Initiative == 0`. **User states this can never happen in practice**, so left as-is.

3. **Random tiebreak picked from ALL players** via `array_slice($players, $rand, 1, true)`. Could promote a non-tied player to First Player.

4. ~~"Next in turn order" tiebreak ignored the tied set.~~ **Not a bug.** I initially "fixed" this by walking `getPlayerAfter` until landing in `$tiedPlayerIds`. User corrected me: the rule is just `getPlayerAfter(previousFirstPlayer)`, no tied-set filter. Reverted to a single hop. Saved as feedback memory `first-player-tiebreak` so I don't re-make this mistake.

5. `setNewPlayerOrder($firstPlayerId)` had no parameter type. Violates the project rule.

6. Three near-duplicate `notifyAllPlayers` + `createEvent` + `nextState` blocks.

7. Silent `$highPlayerId = 0` default if no players had valid schemes.

## Fix

Build a `$tiedPlayerIds` array during the loop — replace whenever a strictly-higher initiative shows up, append on equal. Then:

- `count === 1` → solo winner path (no tiebreak) — fixes #1
- previous first player exists → single `getPlayerAfter()` hop, no filter against tied set — original behavior preserved (NOT a bug; rules say rotate regardless)
- otherwise → `bga_rand(0, count($tiedPlayerIds) - 1)` over the tied set — fixes #3

Also:
- Extracted `setFirstPlayerAndAnnounce(int $playerId, array $players, int $initiative, string $message)` to kill the triplicate notify+event+nextState block (#6). The three call sites still pass their own `clienttranslate(...)` literals so translation extraction still sees them at the call site.
- Typed `setNewPlayerOrder(int $firstPlayerId)` in UtilitiesTrait.php (#5). Also hardens the raw-SQL interpolation in that function against non-int input.
- Added `throw new \Exception(...)` if `$tiedPlayerIds` ends up empty (#7). Per CLAUDE.md I'd normally skip "can't happen" validation, but the user explicitly asked to address #7 and the cost of the throw is one line; better than silently writing `FIRST_PLAYER = 0`.

## WHY notes for future-me

- **The `tiedPlayerIds` array isn't just clean — it's necessary.** Originally there was a `count($players) == 1` band-aid on the solo-winner branch. With Initiative ≥ 1 guaranteed (per user) that band-aid was already dead, but the *tied-set* idea is what made #3 and #4 fix correctly. Don't "simplify" by going back to flag-plus-id.
- **Single `getPlayerAfter` hop, no filter:** Per the rules, when a previous First Player exists and there's an initiative tie, you rotate to the next seat regardless of whether that seat was tied. I initially "fixed" this with a `while (!in_array)` walk and the user corrected me. See feedback memory `first-player-tiebreak`.
- **Why `clienttranslate()` at the call site, not in the helper:** the BGA translation extractor scans source for the literal `clienttranslate('...')` token. Centralizing the call inside the helper would hide the string from extraction.
