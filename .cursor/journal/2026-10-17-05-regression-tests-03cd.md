# PHP regression tests for city-deck 03cd*

## Context
User: continue regression tests for classes `03cd*` and their composites.
Prior band `_02051`–`_02060` done (2026-10-17-04). Silver Spine (`Card_03cd21_Test`) already existed from the Defending Honor fix (2026-10-17-03). No production card edits.

## Inventory
City-deck cards that exist (gaps are real; there is no `_03cd02`, `_03cd04`, etc.):

| Card | Type | Composites |
|---|---|---|
| `_03cd01` Penya | CityCharacter | Action_03cd01 |
| `_03cd03` Chance Meeting | CityEventCard | Action_03cd03 |
| `_03cd05` Devil Jonah's Bones | CityAttachment | (gamble choice is `actFromCardWithId` on the card) |
| `_03cd08` Inauguration Day | CityEventCard | none |
| `_03cd10` Julius Caligari | CityCharacter | Reaction_03cd10 |
| `_03cd12` Equal Claim | CityEventCard | none |
| `_03cd13` Crabs in a Bucket | CityEventCard | Action_03cd13 |
| `_03cd18` Kalla and Adelheide | CityCharacter | Reaction_03cd18 |
| `_03cd20` Early Morning Arrangements | CityEventCard | Action_03cd20, Reaction_03cd20 |
| `_03cd21` Silver Spine | CityAttachment | already tested |

## Status
**DONE.** 17 suites (16 new + existing Spine), **102 passed / 0 failed / 0 errors**.

Docs bumped: CLAUDE.md + `.cursor/rules/run-regression-tests.mdc` now mention city-deck `_03cd01`–`_03cd21`.

Harness (no production card edits):
- FakeGame gained `GAMBLE_REVEAL_FROM_BOTTOM`. Real `Game.php` has it; the test stand-in did not, so `_03cd05` fatal'd on the bottom/top choice.
- `tests/bootstrap.php` polyfills global `mb_substr` when mbstring is missing. Reaction_03cd10 calls it unqualified; PHP falls back to global. Trait names are ASCII so `substr` matches. Do not "fix" the card to `substr` — BGA has mbstring, and non-ASCII traits would diverge.

Not committed.

## WHYs pinned
- **03cd01**: Dashed Combat challenge is rejected in `Character::eventCheckCore` (still runs if the text box is blanked). Intervene throws only when `newTargetId` is Penya. Wound or duel participation in the city cancels the wound, drops the top city card at his *current* location, unequips first (city attachments leave via city discard), then removes him to the City Deck. Empty deck still removes him. Home wound / home duel / someone else's duel do nothing. Shuffle happens on `EventCardRemovedFromPlay` to the City Deck only.
- **Action_03cd01**: Needs him controlled, in the city, unengaged, with another character of *his controller* at the location. Companion args ignore the active player. Two-player Docks adjacency is Forum only (Home excluded). Self / foe / missing / elsewhere throw before `companionChosen`. Non-adjacent throws before any queue. Success: engage Penya, move companion (engage false), move Penya (engage false), action resolved, `locationChosen`.
- **Action_03cd03**: Event is usually uncontrolled. Available to anyone with a character at the card, engaged included. Home does not count. Target list is every seated player. Eligible musters are strictly fewer characters *in play* (city or Home), turn order from `getCollectionFromDB` (playerNames order), target skipped. Nobody eligible discards immediately, attributed to `CURRENT_PLAYER`, and clears `CHOSEN_TARGET` + `REMAINING`. Approach characters only. Id 0 declines. Discard waits until the last seat. Invalid muster throws before the remaining list shrinks. Transitions that test sees: `playerChosen` then `musterResolved`.
- **03cd05**: Equip of this attachment id wounds (abilityId is the card id string) even before `AttachedToId` is written. Gamble prompt only when attached and the actor is the host. `AttachedToId > 0` with a missing character still prompts and uses the event's player. +1 reveal only for the host. Id 2 sets `GAMBLE_REVEAL_FROM_BOTTOM`; any other id on that state clears it. Bare `nextState()`. Other states do not touch the global.
- **03cd08**: Pressure at this city location ORs `CLAUDE_PRESSURE_TYPE` onto `PRESSURE_TYPE` (other bits survive) and sets `CLAUD_ID` to this card. Other locations and a discarded card do nothing.
- **03cd12**: End of High Drama, card in the city, location currently controlled. Counts are per seated player at this location. Home characters do not count. Uncontrolled (controller 0) characters are not a seat. Equal zeros still match, so an empty controlled location falls. A third player at zero breaks a 1-1. `CanBecomeUncontrolled` false notifies and does not queue. Event playerId is the location controller.
- **03cd13 card**: Reveal draws for everyone strictly below the highest Renown. Ties at the top do not draw. All zeros draw nobody. A different card id does nothing.
- **Action_03cd13**: `RequiresPerformerSelected`. Lock is `playersUsed`, not `Used` — success calls `setUsed(false)`. Engaged characters at the location still pass availability but produce no performer button. Must be claimable and you must be strictly behind someone. Args are those richer opponents. Resolution engages even if the location became unclaimable before the click; claim is skipped; the player is still locked. `eventCheck` throws on a second trigger the same day. Dusk clears the list and marks the card updated.
- **Reaction_03cd10**: Offer on his own recruit (city, opposing present) or on `EventCardMoved` using `toLocation` (Location is not written yet). Opposing = controlled by someone else. Uncontrolled neighbors do not count. Uncontrolled Julius moving does not offer. Letters come from TraitNames. After `letter-A` the prompt quotes `"A"`, not the trait. Academic is found by button text (do not hardcode `trait-0` for the wound path; the miss path uses `trait-0` only because every A-trait fails a Bard card). 1–2 card hands are fully revealed, so the wound is deterministic. Empty hand, miss, or missing target still `setUsed`. Back on the letter step (stage must already be `letter` from the offer) requeues and does not spend. Decline spends. Unknown id just `nextState("done")`.
- **Reaction_03cd18**: Own recruit, in the city, and either a deck attachment or an opposing equipped attachment somewhere in the city. Search buttons dedupe by Name; the id is the first deck row. Resolve removes from the faction deck, adds to hand, shuffles. A non-attachment id still spends and does not shuffle. Move is queued before the destroy choice (`engage` false). Destroy buttons have no Back; Decline is still there. Destroy buttons read `owner->Location`, which the queued move has not applied — tests must write Location before expecting the destination's attachment. City attachment destroy is city discard `asEffect`. A same-controller attachment returns early and still spends.
- **Action_03cd20**: Pressure location is the *performer's* location (test puts them at the Forum while the card is at the Docks). Stat is Finesse. Success adds the top city card to the *card's* location, writes `ControllerId` before the move, moves the card Home unengaged. Empty deck still goes Home. Failure resolves the action and leaves controller 0. Other ability ids are ignored.
- **Reaction_03cd20**: Planning end, card at Home, at least one in-play character of `ControllerId`. Locker characters are not in play. Destinations are adjacent to Home, not to the character — a Forum character can go to the Docks. Two-player Home adjacency is Docks/Forum/Bazaar; `PLAYER_COUNT` 4 adds Ole's Inn and the Garden. Illegal location or a character of another controller returns before queueing **and still setUsed**. Decline spends. Back from the location step returns to characters and does not spend. Controller 0 treats uncontrolled city characters as eligible because `getCharactersInPlayByPlayerId` does not require `isControlled`.

## Run
Absolute Windows paths. Relative `tests/regression/...` doubles under `tests/` on this runner.

## Next
`_03001`+ or `_04cd*` when asked. Faction `_03xxx` (non-cd) is a different band.
