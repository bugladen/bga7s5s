# StatesTrait regression tests (tests/regression/framework)

No StatesTrait tests existed before this. User asked for them in a `framework` subfolder. Did it in 8 suites (~175 tests), all green; full `php tests/run.php` = 5246 passed.

## Harness design (WHY)
- `tests/harness/StatesTraitGame.php` = `extends Game` (FakeGame via autoload) + `use StatesTrait`. The trait's `Game::X` constants resolve to FakeGame, so every constant the trait touches had to be added to FakeGame with the *same value* as modules/php/Game.php. If a new st* test errors with "Undefined constant", that's the fix.
- Collaborators (DB, deck, notifications, stats) are stubbed AND recorded as public fields, so tests assert on behavior instead of mocking frameworks.
- Duel DB: stDuel* methods read `duel` / `duel_round` rows. Rather than a fake MySQL, StatesTraitGame routes the few SQL shapes (`MAX(duel_id)`, `from duel`, `from duel_round`, `UPDATE duel_round SET a = n, b = m WHERE`) to `duelRow` / `duelRoundRow`. Tests seed those arrays. The UPDATE parser only handles integer assignments - extend if a new state needs more.
- `TestTheah::runEvents` now records to `runEventsCalls` and leaves queuedEvents intact. Existing card tests were unaffected (full run green).
- Leaders are Characters: park them in 'Leader Zone' in tests or they count against crew cap/influence.
- Don't put abstract/helper TestCase subclasses in test files - run.php requires each file to hold a concrete TestCase.
- Windows: pass test paths to run.php with backslashes.

## Semantics I got wrong first (don't repeat)
- A participant's threat is the threat THEY TAKE. CHALLENGER_THREAT wounds the challenger (source = target, Restricted Hostilities cap uses target's stat). DEFENDER_THREAT wounds the target, cap from the challenger's stat (last-known if dead) unless DEFENDER_THREAT_IS_LETHAL. In stDuelEndOfRound the leftover pool wounds the ACTOR, capped by the ADVERSARY's stat.
- TestTheah::getDuelChallengerId defaults to duelActor->Id; set `duelChallengerIdOverride` when the defender is the actor.
- `stDuelEnd` Terrell check only returns attachments for his controller; the opponent's attachments still discard.

## Findings (NOT fixed - report to user)
1. `stChallengeActionCheckCancelled` with a null/missing target dereferences `$target->ControllerId` (warning then changeActivePlayer(null)). I removed the test for it rather than pin a bug.
2. `stPlanningPhaseMuster` "under their Crew Cap" message has malformed `'<span style=${player_name} ...'` markup.
3. `stDuelNewRound` for round > 1 does not set DUEL_CURRENT_PLAYER or queue ChangeActivePlayer (round 1 does); stDuelNextPlayer does it instead. Looks deliberate - test asserts the split ("player switch is handled by stDuelNextPlayer").
4. `stResolveManeuverFromCombatCard` never calls nextState when there is no DUEL_MANUEVER_ID. Not tested on purpose (would pin possibly-accidental behavior); it's a game state so it would stall if ever reached.

## Mutation sanity check
Flipped 4 conditions in StatesTrait (lethal cap, nullify-on-dead-actor, living-not-colocated end, Terrell controller check) - each made the DuelEnd suite fail. Restored via file rewrite; `git diff` on StatesTrait.php is empty. Earlier suites all passed first try, which made me suspicious, so worth knowing this check was only done on DuelEnd.

## Not covered
- The `_04043::clearPendingDebuff`, `_01089::clearLeftoverDebuffs`, `Reaction_04003a::flushPendingRecovers` helpers run (no-crash) but their effects aren't asserted - they belong in card suites.
- Roughly everything outside StatesTrait (e.g. DeckTrait, other traits).

## Docs
Added `framework` to the suite-folder list in CLAUDE.md and .cursor/rules/run-regression-tests.mdc.

## Opinion
The harness approach scales better than I feared; most cost was constants and DB-shape routing. Biggest risk going forward: the stubbed SQL routing is string-matching, so a refactor of the trait's queries will break tests noisily (fine - better than silently).

## Follow-up: fixed findings 1-3
- **Muster typo**: removed the dangling '<span style=' from the under-cap template. Test checks exact template text and that every ${x} has an arg.
- **stResolveManeuverFromCombatCard**: nextState() moved out of the if. WHY: single "" transition game state; no maneuver id = stall. Test added.
- **stChallengeActionCheckCancelled null target**: new private helper getChallengeTargetControllerId (live target -> last-known snapshot -> null). If null, treat as cancelled rather than changeActivePlayer(null). WHY cancel: with no controller there is nobody to give the Accept UI to, and cancelling is the only branch that doesn't need the target. Accepted challenges skip the lookup (they never needed the target).
- **Reality check on bug 1**: real Theah::getCardById falls back to a DB read, so a Stiletto-destroyed target usually does NOT come back null in production - only an id matching no card does. My earlier "destroyed target" framing overstated it. Only the unresolvable-id test fails on old code; the destroyed-target tests are behavior guards that pass either way. To mirror prod I gave TestTheah a getCardById override (falls back to FakeGame::getCardObjectFromDb) plus removeCardFromWorld. Full suite green after that, so no existing card test relied on the old (crashing) fallback.
- Gotcha: StatesTrait's namespace is Bga\Games\SeventhSeaCityOfFiveSails, so bare Character is NOT cards\Character - use the FQCN in type hints (the first run died on this).
- Not fixed, noticed: stPlanningPhaseMuster leaves  undefined/stale if a player has no Leader row.
