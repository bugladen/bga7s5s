# Tournament Deck Persistence — Audit & Fix

Audited the uncommitted changes in `FrameworkActionsTrait.php` and `State_DeckAssignment.php` that introduce tournament deck persistence via the BGA Tournament API (Beta).

## What was wrong

### 1. JSON round-trip double-encoding

Per `_ide_helper.php:489-519`, `Tournament::storePlayerGameData($playerId, $key, mixed $data)` JSON-encodes `$data`, and `retrievePlayerGameData` JSON-decodes it on the way out. The original code stored `$deck_json` — which is already `addslashes(json_encode($deck))`. So the framework wrapped that string again; on retrieval you'd get back the slashed JSON string, not the deck object. Then `State_DeckAssignment` did `addslashes(json_encode($deck))` on top, writing a re-encoded escaped-string into the `deck_source` column. The deck would not load correctly on subsequent tournament games.

Fix: pass the decoded `$deck` object/array to `storePlayerGameData`. On retrieve you get the structured data directly; encode once for SQL.

### 2. Random-deck path didn't persist

`State_DeckAssignment::onEnteringState` had a random-assignment branch (when `OPTIONS_PLAYER_DECKS !== MANUAL`) that never called `storePlayerGameData`. So tournaments using random starter decks would re-randomize every game — violates "same deck for the whole tournament."

Fix: after the random assignment, call `storePlayerGameData($playerId, 'deck_source', $chosen_deck)` if in a tournament.

### 3. Retrieval only handled one player

`onEnteringState` is `StateType::GAME` — `$activePlayerId` is not meaningful. The original tournament branch retrieved for that single ID and short-circuited. In a 2+ player tournament, only one deck got restored.

Fix: loop `loadPlayersBasicInfos()`, restore everyone with a stored deck (track the ones still missing), and only short-circuit to `buildTable` if **all** players are restored. Otherwise fall through to PICK_DECKS (manual) or random for the players still missing a deck. The pre-restored players have already had their `player.deck_source` updated, so they won't re-pick.

## Why tracking `$playersNeedingDeck` matters

I considered making the tournament branch all-or-nothing (only short-circuit if every player has a stored deck, else ignore tournament storage entirely). But that would discard decks already locked in for some players — surprising and probably wrong. Partial coverage is unlikely in practice but the safer behavior is to honor every stored deck and only re-pick for those without. The pickDecks state should already gate on `setPlayerNonMultiactive` per-player, so pre-restored players just won't be active there. (Worth a smoke-test before shipping.)

## Comments left in code

- `FrameworkActionsTrait.php:148` — short WHY explaining we pass `$deck` not `$deck_json` to avoid double-encoding.
- `State_DeckAssignment.php` — short WHY noting framework already decoded the retrieved value.

## Follow-up: PICK_DECKS init

Added `stPickDecksInit` in `StatesTrait.php` and pointed `States::PICK_DECKS` at it (was `stMultiPlayerInit`). Pattern modeled on `stMultiPlayerInitSansInitiatingPlayer`: set everyone multiactive, then deactivate any player who already has a tournament-stored deck (via `'deckPicked'` transition, matching `actPickDeck`'s deactivation). Handles the mixed case where some players in a tournament already have decks and others don't — only the ones still needing a deck stay active in the picker.

## Caveats / unknowns

- The doc page (linked by user) was truncated by WebFetch; I worked from the IDE helper signatures. They appear authoritative but the beta API may evolve.
- Did not touch the two pre-existing `\BgaUserException` instances on lines 100 and 115 — out of scope, but flagged. Memory note already records BgaUserException is deprecated in favor of `Bga\GameFramework\UserException`.
- Deck removal from the random pool (`array_filter`) may not be desirable — if Player A is restored from tournament storage with deck X, Player B's random pool still contains X. Could cause duplicates. Not introduced by this change but worth noting if it ever bites.
