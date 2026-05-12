# Early Morning Arrangements (`_03cd20`) — implementation

City Event Card with two clauses, one of which puts the card in a player's
Home — the first non-Scheme/non-Character to live there in this codebase.

- **Reaction:** At the end of Planning, if this card is in your Home •
  Move your character to an adjacent City location and discard this card.
- **City Action:** Pressure this location with [Finesse] • If successful,
  add a city card to this location and put this card in your Home.

## Files touched

- `modules/php/cards/faf/_03cd20.php` — wires `IHasActions` + `IHasReactions`,
  `Actions = [new Action_03cd20()]`, `Reactions = [new Reaction_03cd20()]`.
- `modules/php/cards/faf/reactions/Reaction_03cd20.php` — new.
- `modules/php/cards/faf/actions/Action_03cd20.php` — new.
- `modules/php/theah/EventHub.php` — `cardMoved` notify now carries
  `controllerId` (one new array entry).
- `modules/js/Notifications.js` — `notif_cardMoved` now picks up
  `args.controllerId` and writes it back to `card.controllerId` before
  recomputing `cardId` / target.

No new state classes, no `states.inc.php` edits, no per-state JS wiring.
Both abilities are button-based reactions or framework-driven pressure.

## Why button-based Reaction, not a custom state class

Same rationale as Kalla (`_03cd18`) and Julius (`_03cd10`): two small enums
(N characters, ≤5 adjacent locations), and the existing `playerReaction`
framework state already paints `getReactionButtonProperties` for each
stage. Re-queueing `EventFactory::createReactionTransitionEvent` between
stages is the only plumbing needed. A dedicated GameState class would
demand `States.php` constants, `states.inc.php` transitions, and three new
JS handlers for the same UX.

Stages stored on the Reaction as private fields (`$stage`,
`$chosenCharacterId`) and persisted across action handlers via
`$card->IsUpdated = true` — same trick Vittoria (`_01014`) and Kalla
(`_03cd18`) use.

## "Adjacent City" semantics

Interpreted as "adjacent to **this card's** location," i.e., the player's
Home — so destinations are DOCKS / FORUM / BAZAAR (+ OLES_INN at 3p,
+ GARDEN at 4p). Computed via existing
`Theah::getAdjacentCityLocations($owner->Location, $includeHome = false)`.

Alternative read was "adjacent to the **character's** current location."
Rejected because the antecedent for "adjacent" in the card text is the
card sitting in Home, and the home-as-pivot reading gives a clean
3–5-option picker regardless of who's chosen. If rules-judge corrects
this, swap the call to use `$character->Location` as the pivot — one-line
change.

The chosen character can come from anywhere (home or city). Eligibility
is just `getCharactersInPlayByPlayerId($owner->ControllerId)`, which
includes home + city.

## Why `EventCityAction` and `RequiresPerformerSelected = true`

The framework's built-in performer picker runs before
`EventActionTriggered`, drops `CHOSEN_PERFORMER` into the global, and
hands us a ready-to-pressure character. Saves a dedicated "pick performer"
state. `Action_03cd13` (Crabs in a Bucket) and `Action_02035` (Scoundrel
Finesse) use the same pattern.

`getPerformersForAction` filters to non-engaged on top of
`EventCityAction`'s default (friendly chars at this location). Pressure
engages the performer as part of resolution, so an already-engaged
character couldn't pay the cost.

## Pressure flow — straight copy of Action_02035 / Action_02014

```
globals: PRESSURING_PLAYER, PRESSURE_TYPE=NORMAL_PRESSURE_TYPE, PRESSURE_STAT=STAT_FINESSE
queue: createPressureOccuringEvent
queue: createTransitionEvent("pressureLocation", abilityId = $this->Id)
```

No `setGlobalFlag(PRESSURE_TYPE, ...)` — this card doesn't *modify* how
pressure is counted, it just pressures. Action_02014 / Action_02035 set
KASPARS_OCCUPATION / CASTILLIAN_CAPER flags because those abilities also
*change the rule*. Ours doesn't.

`EventLocationPressureResult` later fires back with `abilityId == $this->Id`.
On success: queue `createCityCardAddedToLocationEvent(topOfDeck, location)`
and queue the move-to-home event. Always queue `createActionResolvedEvent`.

## Moving the City Event Card to player Home — the hard part

There was no precedent for a CityEventCard living in a player's Home.
Three things had to line up:

1. **DB**: `card.card_location_arg` must become the player id. Handled
   by `EventCardMoved`'s call to
   `deck->moveCard($card->Id, $event->toLocation, $card->ControllerId)`.
   So I set `$owner->ControllerId = $playerId` and
   `$owner->IsUpdated = true` *before* queueing the move event. The
   in-memory `$card` referenced inside the move handler is the same
   object (Theah caches by id in `$this->cards`), so by the time the
   handler runs, `$card->ControllerId` is already the new player.
2. **Card serialization**: `Theah::runEvents` re-serializes any card
   with `IsUpdated = true` after each event runs. So the new
   `ControllerId` lands in `card_serialized` too. No manual
   `updateCardObjectInDb` call required.
3. **Client**: `notif_cardMoved` used to ignore controllerId entirely.
   The first time I traced this, `createCardId(card, PLAYER_HOME)`
   returned `0-${id}` (old controller still 0) and
   `getTargetElementForLocation(PLAYER_HOME, 0)` returned
   `0-home-anchor`. So I added `controllerId` to the server-side
   `cardMoved` notify (`EventHub.php` ~ line 681) and made the client
   pick it up *before* computing `cardId` / target.

The client-side change is backward-compatible: every existing call site
that sets controllerId server-side at move time is a no-op
(controllerId doesn't change). New behavior only kicks in when
`args.controllerId !== undefined` — which is now always. Worth watching
in QA but no regression risk identified for character / scheme moves,
since their controllerId never flips mid-move.

## "the UI functions for creating cards in the player home do not support creating an event card"

That was the user's note up front. After tracing it, the gap was
*specifically* the `notif_cardMoved` controllerId blind spot, not
`createEventCard`. `createEventCard` already takes a `targetDiv` and
places the card "before" it — pointing it at `${playerId}-home-anchor`
works without any other change. `Setup.js`'s home-cards loop calls
`createCard(divId, card, location)` which already dispatches Event type
to `createEventCard` (line 458–461 of Utilities.js).

So the fix was narrower than the note implied: one new notify field, one
JS assignment. No new templates, no CSS, no home layout work.
Initial render (page refresh while the card sits in a player's home)
already works because:

- `Game::getAllDatas` populates `homeCards` via
  `getCardPropertiesAtLocation(LOCATION_PLAYER_HOME)` — includes our
  card since its `Location` and `ControllerId` are correct.
- `Setup.js` filters by `card.controllerId === parseInt(playerId)`,
  finds our card, falls through Scheme/Leader special cases, and
  invokes `createCard` → `createEventCard`.

## Discard from Home

`createCardAddedToCityDiscardPileEvent($owner->ControllerId, $owner->Id, $owner->Location, $owner->Id, true)` —
`fromLocation` is `LOCATION_PLAYER_HOME` (used only for the log
message), and the handler clears `ControllerId` and sets
`Location = LOCATION_CITY_DISCARD` itself. `notif_cardAddedToCityDiscardPile`
destroys the DOM element, so the home anchor cleans up naturally.

Queued **after** the character-move event in `resolveMoveAndDiscard` so
the log reads top-down: "X moves to Y. Card discarded."

## Pre-commit hook

Reaction extends `CardReaction` — has both `$this->setUsed(` and
`$this->isAvailable(`. ✓
Action extends `EventCityAction`. The hook's regex literally matches
`(CardAction|RiskAction|RiskCityAction)` on the `extends` line, so
`EventCityAction` is NOT caught — `createActionResolvedEvent` isn't
required by the hook. I call it anyway because the framework needs the
event to advance state. ✓

## Things I deliberately didn't do

- **No new CSS for an event card in home.** The existing
  `jstpl_card_event` template is plain (just an image div) and slotting
  it next to characters at home looks fine in the screenshot in my head.
  If layout suffers, add a `_7sfs-event-in-home` modifier class — but
  YAGNI for now.
- **No "move character was canceled" handling in the Reaction's resolve
  step.** If some other card intercepts and cancels the character move,
  the discard still fires. That matches Kalla's behavior at `destroyB`
  and feels right: the *card's* effect was committed when the player
  picked the destination, even if the engine later vetoed the move.
- **No special handling for the case where the destination is the
  character's current location.** A no-op move event is harmless; the
  player just doesn't get value out of it.
- **No `setUsed(false)` on dusk for the Action.** `CardAction::handleEvent`
  already does that. Inheriting the base behavior is enough.

## Risks / things to verify in play

- **Multi-player simultaneity at end of Planning.** If two players both
  have this card in home, both reactions queue. The framework's reaction
  system resolves them in priority/turn order. I haven't traced that
  exhaustively; if it breaks, the symptom would be one of the reactions
  being silently consumed without prompting. Worth a multi-player smoke
  test.
- **Re-acquisition.** Player A pressures successfully → card in A's
  home. End of Planning: A uses Reaction to move + discard. Card now in
  city discard. Discard reshuffles into city deck. Next time it's
  drawn to a city location, ControllerId should be 0 again. The
  discard handler clears `ControllerId` already, and shuffle preserves
  it.
- **CardReaction `Used` flag.** `setUsed(true)` after the resolve fires.
  Reset at `EventDuskEndOfDay`. If the card is in city discard at that
  point, the reset is a no-op for behavior (the card's not at home so
  the trigger guard fails anyway), but the flag does flip — which is
  correct for the eventual re-entry into play.
