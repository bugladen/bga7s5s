# ArgumentsTrait regression tests

User asked for feasibility, then "Write the regression tests." Suites shipped.

## Harness

`FrameworkActionsGame` already `use`s ArgumentsTrait (choose-next labels). Added:

- `filterPerformerIdsForExtraAction` mirror (same WHY as getExtraActionPerformerId — don't pull UtilitiesTrait)
- `playedTechniquesCount` + SQL route for `count(*) FROM duel_round_technique` (argsChooseDuelAction techniquesAvailable gate)

`GenericBrute` now sets `Title = ''` — getPropertyArray (PayForBrute) accesses typed Title.

## Suites (51 asserts)

- `ArgumentsTrait_PlayerTurn_Test` — argsEmpty, starter decks, when-revealed order, argPlayerTurn lock vs unlock, choose-next args + notify fog
- `ArgumentsTrait_HighDrama_Test` — move/recruit/equip/claim/challenge pickers, Extra Action lock, DashedCombat/Influence, Carmella engaged challenge, chooserId ≠ active player, Defending Honor skips ability filter, in-play/in-hand/brute
- `ArgumentsTrait_ChallengeAccept_Test` — intervene lists, Carmella/Rena/Vladislav specials, Raven/Valeri/Torvo empty, Aja/Celerity finesse, Sworn Swords, mustDiscardToRefuse, Daichi/Knives Out flags, CHOSEN_LOCATION vs locker, last-known finesse
- `ArgumentsTrait_Duel_Test` — Vladislav end-only, combat/technique/gamble button matrix, revealCount 0 hide, pending maneuvers, technique/maneuver/pay/gamble peek (actor deck, from-bottom)

## Gotchas / WHY

- StatesTraitGame overrides `getChallengeLastKnownCharacter` → seed `$game->lastKnownCharacters`, not FakeGame's `$challengeLastKnownById`
- FakeGame `characterIsInDiscardOrLocker` is knobbed (`discardOrLockerByCardId`); Location string alone is not enough
- TestTheah `getCardById` always DB-falls-back, so Accept's null→getCardObjectFromDb branch is unreachable in harness — skipped that test, pinned last-known finesse instead
- Windows: `php tests/run.php` needs backslash paths for single-suite runs

## Not covered (honest)

- `argsForState` / `argsForStatePrivate` (thin card dispatch)
- `argsPlanningPhaseResolveSchemes_01016_3` (card-specific)
- Full `previewChallengeThreat` technique modifiers (Theah capabilities pins base)
- Equip pay-from-play / choose attachment location (shape mirrors from-hand/from-play already covered)
- In-play Extra Action action filtering (heavy actionAvailableToPerformer wiring)

## Verification

ArgumentsTrait suites alone: 51 passed. Full `php tests/run.php` → **5763 passed, 0 failed, 0 errors**.

## Opinion

Cleaner than EventHub. Highest-value pins are chooserId-after-UL, duel hub button matrix, and Accept intervene/refuse flags — those are the ones that silently empty UI without failing an act* test.
