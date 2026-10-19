# Regression tests `_04cd*`

Continuation of `2026-10-19-04` (faction `_03051`–`_03073` done). User: keep going on classes `04cd*` and every composite. No production card edits. Not committed.

17 new suites, 85 cases. Full `php tests/run.php`: **4834 passed, 0 failed, 0 errors** (was 4749). Docs bumped to city-deck `_04cd01`–`_04cd29` in `CLAUDE.md` and `.cursor/rules/run-regression-tests.mdc`. Gaps (`_04cd03`, `_04cd05`, …) are real; those files do not exist.

## Inventory

| Card | Type | Composites |
|---|---|---|
| `_04cd01` Penya (bas attachment, not faf Penya) | CityAttachment | Action_04cd01, Action_04cd01b, `_04cd01_RiskClone` |
| `_04cd02` Jack Trades | CityCharacter | none (passive) |
| `_04cd04` Astrid | CityCharacter | Action_04cd04 |
| `_04cd07` Festival of Fools | CityEventCard | none |
| `_04cd09` Knives Out | CityEventCard | Action_04cd09 |
| `_04cd11` Let Bygones Be Bygones | CityEventCard | dusk choice is `actFromCardWithId` |
| `_04cd14` Millstone | CityCharacter | two `Technique_PlusOneThrust` with ClassIds `Technique_04cd14_1` / `_2`. Shared class, not a 04cd file. Recruit picker is on the card. |
| `_04cd15` Puzzle Box | CityAttachment | Action_04cd15 |
| `_04cd19` Blood in the Water | CityEventCard | none |
| `_04cd29` Tijani | CityCharacter | Action_04cd29, Technique_04cd29 |

## WHYs pinned

- **Action_04cd01.** Cost engages the attachment, not the host. Host may already be engaged. Home is not a destination (`includeHome` false) and the host must be in the city. Trigger player is the attachment `ControllerId`, even if the triggering event's player differs. Missing host throws before the adjacency check. Move is `engage` false. Controller 0 still offers the action: this one does NOT add Astrid's `isControlled()` gate, so CardAction's "uncontrolled passes for everyone" leak is live.
- **Action_04cd01b.** Used on an equipped copy is soft-cleared (and the attachment written) before the parent check. Used while unequipped stays used. Engaged does not hide it. Own discard and unaffordable risks (cost 200 vs harness wealth 99) do not qualify. `_01105` needs a city character and `RequiresPerformerSelected`, so commit opens `inHandActionChoosePerformer`. `_01028` does not, so commit queues `EventEnteringPayState` on the **clone** id and `inHandActionPay`. Equipped commit clears Used, unequips, removes from play, sinks to the City Deck bottom (`onTop` false). Unequipped commit skips that sink. Args list every risk in the discard; the action list is only the affordable ones. Clone id is `{cloneId}_Action_01105` because `setOwnerId` keeps ClassId.
- **`_04cd01_RiskClone`.** Discard of the shell hides it and sinks the original (`ClonedCardId`) to the bottom of that card's faction deck, then `createActionResolvedEvent` for the clone's controller. Another card's discard is silent. Do not fire this with `ClonedCardId` 0 — `getCardObjectFromDb` returns null and `->ControllerId` fatals.
- **`_04cd02`.** Copyable traits are only Diplomat/Duelist/Pirate/Zealot, tracked in `CopiedTraits` so a Diplomat he already had (attachment-style `addTrait`) is not stripped. `EventCardMoved` is before Location updates: arrival is read off the event, departure is excluded even though still listed. Opponents do not grant. Home uses `getCharactersAtHomeByPlayerId`. An opponent moving to Home is ignored (`toLocation == Home` and controller mismatch). Destroy excludes the corpse before the hub moves them. Locker (`discardOrLockerByCardId`) does not grant. Uncontrolled Jack ignores moves. His own muster/recruit passes `playerId` as the controller override: 0 clears grants, 1 copies that player's allies while his `ControllerId` stays 0.
- **`_04cd04` / Action.** Influence +1 only while `!Engaged` and `PRESSURING_PLAYER` is someone else (including not 0). The action requires `isControlled()` — the leak Penya's move does not close. Destinations are adjacent, `Controller != 0`, and `CanBecomeUncontrolled`. Her own claim counts. Home is a legal origin. Trigger uses the event player, not a stored controller. Engage her, then uncontrol, then move `engage` false. The "cannot become uncontrolled" notify after the eligibility check is unreachable without a hook between the two identical checks; not tested.
- **`_04cd07`.** Reveal draws for every `loadPlayersBasicInfos` row. End of High Drama uses `CityLocation->Controller` (not `Game::getControllerForLocation`). Controller is skipped. Controller 0 skips nobody. Home characters do not count. Card outside the city is silent.
- **`_04cd09`.** `challengeRefusalBlocked` is any defender at a city location that contains this card. No challenge type. Home and a discarded card do not block.
- **Action_04cd09.** `RequiresPerformerSelected` stays false. Need your character here, then an unengaged one or a hand card. Destinations are every other seeded city location, not adjacency, not Home. Illegal cost / foe performer / opponent hand / same location throw before queue. Engage and discard both queue a `04cd09_3` transition and `nextState("costPaid")`. Discard is `asEffect`, not payment, not played. Resolve move is `engage` false, then `setUsed(false)` (unlimited) and both globals cleared.
- **`_04cd11`.** Dusk prompts in `getCollectionFromDB` order, only players with a character here, only while the card is in the city. Choice stamps `LET_BYGONES_BE_BYGONES` and heals 1. `eventCheck` is on `EventCardMoved` to Home: the condition is removed **before** the throw, so a second home move is not this card's problem. Other destinations keep the stamp. Call `eventCheck` directly; `TestTheah::eventCheck` does not walk cards.
- **`_04cd14`.** Two thrust techniques need distinct ClassIds or they collide. Recruit always wounds him. The `04cd14` picker opens only when `getOpposingCharactersAtLocation` is non-empty (controlled, other controller, same location). Uncontrolled neighbors do not count. `nextState()` is recorded null.
- **`_04cd15`.** Equip adds Sorcerer; unequip removes it. Other attachment ids are ignored.
- **Action_04cd15.** Empty deck is still available and trigger goes to `04cd15_3`. Cards open `04cd15`. Used is not soft-cleared (unlike 04cd01b). Sink validates every id before any insert. Pass with 2+ remaining does not insert and goes to reorder. Pass with 1 inserts on top then `discardChoice`. Sinking down to 0 is `discardChoice` with only the bottom inserts. Reorder with a short list throws before inserts. Reorder whose later id is unknown still inserts the earlier ids — pin that, do not "fix" the test to expect a rollback. Discard-to-draw is the controller's hand, `asEffect`, then draw and resolve. Pass on the discard step resolves with no draw.
- **`_04cd19`.** Reveal renown uses `$event->location`, not `$this->Location`, and does not require `cardInCity`. Engage wound requires the card in the city, a Character at that location, and `!$canceled`. Attachment engages do nothing. A null `batchId` is minted onto the in-flight engage (first id is 1) and copied onto the wound. An existing batch is reused.
- **Action_04cd29.** Allies with lower Finesse at an adjacent city location qualify. Same location, Bazaar from the Docks (2p), equal Finesse, engaged Tijani, Home, and uncontrolled Tijani do not. `ControllerId` 0 on resolve uses the active player (`?:`).
- **Technique_04cd29.** `IN_DUEL` + `DUEL_GAMBLED` + he is `duelActor` + adversary Finesse strictly lower. Blanked text hides it. Calculate does not re-check: a tie wounds nobody but still `setUsed(true)`.

## Run

Absolute Windows paths. Relative `tests/regression/...` doubles under `tests/` on this runner.
