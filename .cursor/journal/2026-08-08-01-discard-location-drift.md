# Discard pile location drift — serialized Card->Location vs card_location

## The report

Eddie had a live game wedged in `highDramaPhase01069_2` (Maxime de Lafayette's
"Recover Attachment from Discard Pile"). The attachment showed up in the
selection UI, but confirming it threw "Card not in your discard pile". He'd
already dug into the DB and handed me the smoking gun: `card_location` =
`Discard-2370063`, serialized `Location` = `Faction-2370063`.

That saved me an hour. The diagnosis was basically handed over; the work was
finding where the drift comes from and deciding where to repair it.

## Why the two disagree

There are two parallel records of where a card is:

1. `card.card_location` — the BGA Deck component's column. This is what the UI
   sees and what `DB::getCardObjectsAtLocation()` queries.
2. `card.card_serialized` → `Card->Location` — a property on the serialized
   object, maintained *by hand* at every move site.

`Action_01069::getArgsFromAction` builds the picker from (1); its
`actFromActionWithId` validates against (2). Any drift between them turns into
"the game offers you a card and then refuses it" — an unrecoverable state,
because there's no way to back out of `_2`.

## Where the drift is created

Cards start life in the faction deck with serialized `Location =
"Faction-<pid>"` (`createCardInLocation` in setup). Any path that later moves
the row to a discard pile with `$deck->moveCard()` alone leaves that string
frozen. Found five such sites:

- `Action_01134:254` — mill from a faction deck / City Deck into the discard
- `Action_02002:168` — same shape, Tooth and Claw version
- `Action_02014:143` — City Deck → City Discard
- `_01163_CardClone:73` and `_01154_RiskClone:42` — cloned card returned to the
  owner's discard

The first two are the ones that produce exactly the Faction→Discard drift Eddie
saw. Which of the two hit his game I can't tell without the DB, and it doesn't
matter — both are wrong the same way.

Everything routed through EventHub is fine, because those handlers set
`$card->Location` and `IsUpdated`. This is purely a "card code bypassed the
event system" class of bug.

## Fix: repair at load, not just at the sites

Two layers:

**1. Self-heal in `Theah::buildCity()`** (`repairDiscardPileLocations`). When
loading each player's discard pile, any card whose serialized `Location`
disagrees with the pile it was queried from gets corrected and persisted.

WHY here and not a drift-tolerant check inside `Action_01069`: the same
`$card->Location != $discardName` guard exists in at least eight other places
(`Action_01024`, `Action_01113`, `Action_01167`, `Action_02008`, `_01044`,
`Maneuver_01113`, `Reaction_04003a`, ...). Patching one card fixes one card;
patching the load path fixes every consumer at once and unsticks the live game
on the next click, since `buildCity()` runs at the top of essentially every args
and action handler.

WHY it's safe to treat `card_location` as authoritative *for discard piles
specifically*: a card sitting in a discard pile has no legitimate reason to
report anything else. I deliberately did **not** generalise this to all
locations. Purgatory is the counterexample — `Action_01069` step 1 parks the
chosen hand card in Purgatory via `moveCard` while intentionally leaving
`Location = Hand`, and step 2 relies on that to build the discard-from-hand
event. A blanket reconcile in `buildCity()` would quietly break that. Equipped
attachments are another case where `card_location` tracks the host character's
city location. Discard piles are the narrow, unambiguous slice.

**2. Fixed the five sites** so they stop producing drift in the first place.
The repair layer alone would mask them forever, and masked bugs come back
wearing a different hat (City Discard, for instance, is *not* loaded by
`buildCity()`, so `Action_02014` gets no self-heal — it had to be fixed at the
source).

I put the `Location` update *after* the notification in each case, not before.
The notifications pass `getPropertyArray()`, which includes the location, and
the client may use it as the animation origin. Changing what the client sees
would be a separate, unrequested behaviour change.

## What I'd flag for next time

The real structural problem is that `Card->Location` is a hand-maintained
duplicate of a column the framework already owns. Every new card that calls
`$deck->moveCard()` directly is a fresh opportunity to reintroduce this. Two
options if it keeps happening:

- Make `Location` a read-through accessor over `card_location` and delete the
  stored copy. Big change, touches serialization, but kills the bug class.
- Wrap `moveCard` in a `Game::moveCardTo()` that updates both, and forbid raw
  `$deck->moveCard()` in card code via the pre-commit hook.

The second is cheap and I'd lean that way, but it's Eddie's call — I didn't want
to expand a stuck-game fix into a framework refactor.

Not verified against a live game; there's no local runner for this project, so
this is reasoning-verified only. The thing I'd most want confirmation on is that
the stuck game does in fact resolve on the next click rather than needing a
manual DB touch.

## Follow-up: Game helpers migration (card PHP files)

Parent agent added `Game::moveCard` / `moveCardInDeck` / `parkCard` on DeckTrait
(the cheap option from above). This session migrated all raw `$deck->moveCard`
call sites under `modules/php/cards/` on **main**.

WHY the three helpers map the way they do (don't "simplify" these back):

- `parkCard` for Action_01069 / Action_01156 — deck row goes to Purgatory but
  serialized `Location` must stay Hand. Step 2 builds the discard-from-hand
  event from that. Using `moveCard` here would reintroduce the exact bug the
  buildCity repair deliberately does NOT cover (Purgatory is the counterexample
  called out above).
- `moveCardInDeck` for `_01126` (go-home before EventCardMoving) and `_01151`
  (city card before CityCardAddedToLocation) — Location is updated by the
  queued event handler. Premature Location write would race/double-set.
- `moveCard(..., $card)` everywhere else — updates deck row AND Location,
  persists. Pass the in-memory Card so world object stays in sync without a
  separate Location= / updateCardObjectInDb pair.

Skipped: `bas/_04cd01_RiskClone.php` and `bas/actions/Action_04cd01b.php` —
those files exist on the `bas` branch only, not on disk under current `main`.
Same migration pattern applies when bas is merged/checked out: hide →
`$game->moveCard($riskCard->Id, LOCATION_PERMANENTLY_HIDDEN, 0, $riskCard)`.

Did not touch EventHub / FrameworkActionsTrait / StatesTrait / UtilitiesTrait /
DeckTrait — parent agent owns those.

## Follow-up: core (non-cards) Game helper migration

Migrated all raw `$deck->moveCard` / `$this->cards->moveCard` in:
EventHub, FrameworkActionsTrait, StatesTrait, UtilitiesTrait.

Notable WHYs from this pass:
- EventCardRemovedFromPlayerDiscardPile permanentlyHide: was deck-only with NO
  Location update — fixed to `moveCard` (same drift class as discard mills).
- EventCharacterDestroyed + CityDiscard/DiscardFromPlay Character recreate paths:
  `moveCardInDeck` then set Location on the new instance — do NOT use moveCard
  before recreate or the Location write lands on the throwaway old object.
- FrameworkActionsTrait day-plan / dusk discard: `parkCard` (Location stays
  Approach/Hand). Risk play TO purgatory: `moveCard` (Location must update).
- CharacterPutIntoApproachDeck: locationArg is `$event->playerId` (real arg);
  do not confuse with the ignored getGameDeckObject($playerId) spurious arg.

Verify: `rg '->moveCard\(' modules/php` should only hit DeckTrait internals +
Game::moveCard call sites / comments.


