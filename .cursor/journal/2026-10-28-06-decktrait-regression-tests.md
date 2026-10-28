# DeckTrait regression tests

User asked for regression tests for DeckTrait.php after the ArgumentsTrait wave.

## Approach

Same pattern as UtilitiesTraitHost: dedicated `DeckTraitHost` + in-RAM `MemoryDeck`, do **not** `use DeckTrait` on FakeGame (card suites depend on `Deck-` naming, stubbed peeks, recording parkCard/moveCard).

Host extends FakeGame only so `getCardPropertiesInLocation` → `getPropertyArray(Game)` type-checks. Aliased trait methods that lack return types FakeGame stubs declare (`getGameDeckObject`, pile name helpers, peeks, `updateCardObjectInDb`) and re-wrapped with compatible signatures. `updateCardObjectInDb` still calls the trait (SQL recorded) then keeps RAM `dbCards`.

`MemoryDeck`: index 0 = bottom, last = top. Dual-store by design — `moveCardInDeck`/`parkCard`/`insertCardOnExtreme` can leave `Card->Location` lagging the deck row.

## Suites (27 asserts)

- `DeckTrait_Move_Test` — Faction-/Discard-/Locker- names, moveCard Location+notif, Theah prefer, moveCardInDeck/park lag, insert extreme, factionDeckCount batch/nest/noop, getCardPropertiesInLocation filter, createCardInLocation
- `DeckTrait_Draw_Test` — normal draw, empty→reshuffle+Used clear+batched count, mid-gamble skip peek (actor only), non-actor / DUEL_GAMBLED / FROM_BOTTOM no-skip, Ivy shrink when nothing under peek
- `DeckTrait_PeekShuffle_Test` — faction/city top peeks, short-deck reshuffle restacks revealed tops, bottoms, shuffle clears Used + batches counts

## Not covered (honest)

- `buildDecks` (CityDecks JSON, tableOptions, player.deck_source SQL, leader stats) — table-setup, not unit-friendly
- Real BGA Deck ordering / shuffle randomness (MemoryDeck reverses deterministically)
- `notifyPlayer` approach-deck path inside buildDecks

## Gotchas / WHY

- FakeGame `getPlayerFactionDeckName` returns `Deck-N`; production DeckTrait returns `Faction-N`. `maybeNotifyFactionDeckCounts` keys on `Faction-` prefix — host must use the real trait naming or count notifs never fire.
- `addAction(..., $host, false)` before place — notify path hits uninitialized `CardAction::$OwnerId`.
- Windows: `php tests/run.php` needs backslash paths for single-suite runs.

## Verification

DeckTrait suites alone: 27 passed. Full `php tests/run.php` → **5790 passed, 0 failed, 0 errors**.

## Opinion

Highest-value pins are twin-store move vs park, factionDeckCount batch nesting (reshuffle-inside-draw), and the mid-gamble peek skip matrix — those are the ones that silently wedge UI or steal revealed gamble cards. buildDecks left alone on purpose.

## Commit blocked by raw moveCard ban

Pre-commit rule 12 greps `($this->cards|$deck|$deckObject)->moveCard(` outside DeckTrait.php. `DeckTraitHost::place()` was seeding via `$this->cards->moveCard` — intentional twin-store seed, but the hook does not exempt the harness for this rule (other card-impl greps are skipped under `tests/harness/*`; moveCard ban still applies).

Fix: `place()` now calls `$this->moveCardInDeck(...)`. WHY not Game::moveCard: Location is already stamped on the object; moveCard would also fire seed-time factionDeckCount notifs. WHY not exempt the harness in the hook: the ban's point is forcing Game API at call sites; MemoryDeck still implements `moveCard` because DeckTrait itself calls `$this->cards->moveCard` (allowed in DeckTrait.php). Re-ran 27 DeckTrait asserts green after the swap.
