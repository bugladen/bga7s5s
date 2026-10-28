# UtilitiesTrait regression tests

User asked for regression tests for UtilitiesTrait. End-of-game stats suite already existed (`UtilitiesTrait_EndOfGameStats_Test` + `EndOfGameStatsHost`). Expanded coverage under `tests/regression/framework/`.

## Harness

`UtilitiesTraitHost` — same WHY as EndOfGameStatsHost: do **not** `use UtilitiesTrait` on FakeGame (overrides pressureLocation / hand stubs card suites need; some signature mismatches fatal at load). Host supplies globals, notify, gamestate, TestTheah, RAM `$cards` deck, bga score/stats, and overrides `getCardObjectFromDb` for hand helpers (real trait method is SQL + safeUnserialize; safeUnserialize tested directly).

`EndOfGameStatsHost` now extends UtilitiesTraitHost (thin alias).

FakeGame gained missing pressure constants Valeri/Overcoat/Orel/OrelWinTies + VALERI_ID/OREL_ID + EVENT_BATCH_ID + CHALLENGE_LAST_KNOWN_* so `Game::` in the trait resolves when host runs.

Gotcha: PHP method names are case-insensitive — defining both `getCollectionFromDB` and `getCollectionFromDb` fatals. One method serves both call sites.

## Suites (55 new assertions across 5 files + 3 existing end-of-game)

- Helpers — wealth payment, card class/instantiate, location globals, flags, batch id, scores, goToEndOfGame, discard/locker, reaction state, hands, attachment type/OffHand, equip restrictions, extra-action lock, fewest characters, turn order
- SafeUnserialize — happy path, length repair, protected `\0*\0` repair, garbage throw
- Pressure — win/lose/tie, Tabard win-ties, Overcoat fail, Reputation Meritée merc filter, Claude en-garde filter, Loyal/Vantage/Pack Tactics/Caper/Trial/Strand/Valeri/Orel/MotM+merc, Combat stat
- Challenge — last-known snapshots, clear globals, clear markers (+ null/missing ids)
- LocationInfluence — base sums, Solomonia adjacency, Constanzo+Thug, Valeri Covert gate, Orel standing +1, notify, eventAffects instanceof list

## Production fix (safeUnserialize order)

Protected-property repair used to run **after** length repair. Corrupted dumps keep declared length including two null bytes (`s:9:"*secret"`) but strip `\0*\0`. Length-first rewrote that to `s:7:"*secret"`, which drops the +2 signal Fix-protected needs. Result: unserialize could "succeed" with a public `*secret` property while the real protected stayed at class default — easy false green if the default matched the expected value.

Swapped order: protected null-byte reinsert first, then length fix. WHY comment in UtilitiesTrait. Probe target class defaults `secret=0` so a failed repair cannot pass by keeping the default.

## Not covered (honest)

- `getDuelRows` / `revealFirstCardTypeFromCityDeck` / `createRiskAttachment` — heavy Deck/SQL; leave to card suites that stub them on FakeGame
- `queryStandardTables` — private BGA lock helper
- Constanza / Solomonia mid-pressure *flags* inside pressureLocation (standing chips covered; mid-pressure Solomonia/Constanzo use same shape as Loyal and are less unique than MotM’s fresh-query WHY)
- Source-lock tests in Action_01072 / _01075 / _01143 still exist; real pressureLocation coverage now supersedes them for behavior, but source-locks remain as cheap guards

## Verification

`php tests/run.php` → 5636 passed, 0 failed.

## Opinion

Pressure suite was the high-value part — FakeGame has stubbed it forever and card suites only source-locked tie-win strings. Host approach scaled fine. The safeUnserialize order bug was a nice find; without a fixture whose default differs from the serialized value, the old code looked green.
