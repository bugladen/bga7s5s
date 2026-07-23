> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Reusable Sub-Patterns

### "Controls fewer/more characters than X"

Use `Theah::getCharactersInPlayByPlayerId($playerId)` — it counts characters in the city or at player home, and correctly excludes purgatory, hand, and dueling line. Iterate other players via `ORDER BY turn_order` from the player table (turn_order = today's initiative order). Always exclude the comparison target themselves from the eligible set.

### Muster from approach deck

Reuse the pattern from `_7s5s/actions/Action_01072.php` (Réputation Méritée):

1. Get the player's cards in `Game::LOCATION_APPROACH`.
2. Filter to `instanceof Character`.
3. Validate the chosen card id is in that filtered set.
4. `EventFactory::createCharacterMusteredEvent($playerId, $cardId, $location)` and queue it. The EventHub handler moves the character into play and fires any reactions (e.g. crew cap — handled centrally, no card-specific work needed).

For "may Muster" text, also accept id=0 as Decline and skip the muster event. Wire a Decline button in `OnUpdateActionButtons.faf.js` for that state.

If your state reuses the client-side `onMusterCardSelected` flow, **extend the action map in `modules/js/PlayerActions.js`** to include your state name (e.g. `highDramaPhase03cd03_2`). Forgetting this is a common cause of "the muster button does nothing in my new state."

### Target-a-player picker

Store the chosen target as `Game::CHOSEN_TARGET` (a shared global). Mirror the 02002 Elisabetta pattern in `OnUpdateActionButtons.faf.js`: render one button per non-self player. Clear `CHOSEN_TARGET` in your discard step and as a defensive reset in `stNextPlayer`.

**Server-side filtering of eligible targets.** When the card text gates targets ("target a player with more Renown than you", "target a player who controls fewer characters than you"), compute the eligible list in `getArgsFromAction` and send only those players to the client — don't send everyone and filter in JS. This also lets you reuse the same eligibility helper from `isAvailableToPlayer` to suppress the City Action button entirely when no valid target exists. See `Action_03cd13::getEligibleTargetPlayerIds()` for the canonical shape.

### Per-player once-per-Day City Action (card stays in play)

When the City Action does NOT discard the card and each player can take it once per Day (Siren's Scream `_01179`, Crabs in a Bucket `_03cd13`):

1. **Private `$playersUsed` array on the Action class** — holds player ids that have used the ability this Day. NOT on the card class (the base `CityEventCard::playersThatUsedMeToday` is private to the card and not accessible from Actions). Action_01179 / Action_03cd13 both define their own.
2. **`isAvailableToPlayer`** — return `false` if `in_array($playerId, $this->playersUsed)`.
3. **`eventCheck`** — re-validate `EventActionTriggered && actionId == $this->Id`, throw `UserException` if `playersUsed` contains the player. Defense in depth against race conditions / stale clients.
4. **In the action's `handleEvent`** after the effect resolves: `$this->playersUsed[] = $playerId;` then `$this->notifyUsedList($game, $owner->Id)` and `$this->setUsed($event->theah, false)` (defensive — keeps the framework from marking the card consumed).
5. **Reset on dusk**: `if ($event instanceof EventDuskEndOfDay) { $this->playersUsed = []; $card->IsUpdated = true; $this->notifyUsedList($game, $card->Id); }` — sends a notify with an empty list so clients clear the per-card UI.
6. **Do NOT queue `createCardAddedToCityDiscardPileEvent`** — the card stays.

Critical: per-player tracking is the *only* gate. The card's `Used` flag is global ("any player has used it") and would lock everyone out after the first use.

### Display per-player usage on the card element

The Siren's Scream / Crabs in a Bucket UI: a small list of player-colored names overlaid on the city card image, showing who has used the City Action this Day. Reuse the existing `_7sfs-card-player-list` CSS class — no new styles needed. Pattern (copy from `_01179` end-to-end):

1. **Action class** — `public function getUsedListData(Game $game): array` returning `[{playerId, playerName, playerColor}, ...]`. `private function notifyUsedList(Game $game, int $cardId)` emits a `xxxUsedListUpdated` notification with `{cardId, usedList}`.
2. **Card class** — `public function getXxxUsedListData(Game $game): array` that delegates: `return $this->Actions[0]->getUsedListData($game);`. Thin delegate so `Game::getAllDatas` can fetch it without poking the Action directly.
3. **`Game.php::getAllDatas`** — add a branch in the `foreach ($this->theah->getAllCards() as $card)` loop:
   ```php
   if ($card instanceof cards\<expansion>\<class> && $this->theah->cardInCity($card)) {
       $result["xxxUsedList"] = ['cardId' => $card->Id, 'usedList' => $card->getXxxUsedListData($this)];
   }
   ```
   Initialize `$result["xxxUsedList"] = null;` before the loop.
4. **`modules/js/Templates.js`** — `window.jstpl_xxx_used_list = \`<div id="xxx-used-list" class="_7sfs-card-player-list"></div>\`;`.
5. **`modules/js/Utilities.js`** — `displayXxxUsedList(cardId, usedList)` and `removeXxxUsedList()` mirroring `displaySirensScreamUsedList`. The display function `dojo.place`s the template inside `${card.divId}_image`, then iterates `usedList` creating colored `<span>` children.
6. **`modules/js/Notifications.js`** — register channel `['xxxUsedListUpdated', 1]` (priority 1 = instant UI update) and add `notif_xxxUsedListUpdated` handler that calls `displayXxxUsedList`.
7. **`modules/js/Setup.js`** — `if (gamedatas.xxxUsedList && gamedatas.xxxUsedList.usedList.length > 0) { this.displayXxxUsedList(...) }` for page-refresh.

Gotcha: the Action class is the natural owner of the data because `handleEvent` already has `$event->theah->game` for emitting notifies. The card-class delegate exists purely so `getAllDatas` can find the right Action without reflection.

### Highlight the chosen performer in a target-picker state

For a target-player state that follows performer selection (e.g. Crabs in a Bucket), add `performerId` to the state args and highlight it in the JS so the player sees which character they're about to engage:

1. **Action `getArgsFromAction`**: `$args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);`
2. **`OnEnteringState.faf.js`**: `this.highlightCharacterChosen(args.args.args.performerId)`, and stash `this.clientStateArgs.performerId = performerId` for the leave handler.
3. **`OnLeavingState.faf.js`**: `this.unhighlightCharacterChosen(this.clientStateArgs.performerId)`.

These helpers (`highlightCharacterChosen` / `unhighlightCharacterChosen`) are the same ones `highDramaPhase03cd01` uses.

### `_7sfs-chosen` cleanup for cards that aren't discarded

`03cd03` adds `_7sfs-chosen` to the city card image on enter but **has no leave handler** — that's only fine because the card is discarded immediately after the action resolves, so the DOM element vanishes. For a multi-use City Action where the card stays in play (e.g. `03cd13`), you MUST add a leave handler that removes `_7sfs-chosen`, otherwise the highlight persists into subsequent High Drama turns. Stash the cardId on `clientStateArgs` during enter so leave can find the element after `gamedatas` has moved on.

### "When this card is revealed" Forced

City events use **`EventCityCardAddedToLocation`**, not a generic reveal event:

```php
if ($event instanceof EventCityCardAddedToLocation && $event->cardId == $this->Id)
{
    // ...
}
```

WHY: the city deck places the card onto a location; that placement *is* the reveal. `_03cd13` (conditional draws) and `bas/_04cd07` (unconditional each-player draw) both use this gate. Do not invent `EventCardRevealed` / scheme-style reveal listeners for city events.

### Queueing draws for "each player draws a card"

Always `EventFactory::createCardDrawnEvent($playerId, $this->getInjectCode())` + `$theah->queueEvent(...)`. Do not call deck-draw helpers directly from the card — the draw event owns the notify / hand update path. Emit one `${card_inject_code}: ...` message before the loop (or once the first eligible player is found — see `bas/_04cd07`'s `$drewAny` so you stay silent when nobody qualifies).

### "At the end of High Drama" Forced trigger

`EventHighDramaPhaseEnd` exists and is dispatched centrally by `StatesTrait` at high drama end. Listen for it directly in `handleEvent`:

```php
if ($event instanceof EventHighDramaPhaseEnd && $event->theah->cardInCity($this))
{
    // ...
}
```

Reference cards: `_03cd12` (Equal Claim, makes location uncontrolled), `bas/_04cd07` (non-controller presence draws), `_7s5s/_01025_Burden` (removes itself at end of high drama). Note `_01025_Burden` is an Attachment, not a CityEventCard, but the trigger plumbing is identical.

Do **not** invent a custom "end of high drama" hook or piggyback on `EventDuskEndOfDay` / `EventPhaseHighDrama` — they fire at the wrong granularity.

### "Player with a character at this location that does not control this location"

Two independent checks per player from `loadPlayersBasicInfos()`:

1. **Presence:** `count($theah->getCharactersAtLocationByPlayerId($this->Location, $playerId)) > 0`
2. **Non-controller:** `$playerId != $theah->getCityLocation($this->Location)->Controller`

WHY details that are easy to get wrong:

- **Do not** early-return on `!$location->isControlled()`. Equal Claim (`_03cd12`) does that because its effect is "become uncontrolled" — a no-op when already uncontrolled. For non-controller draws (`bas/_04cd07`), an uncontrolled location (`Controller == 0`) means *every* present player qualifies, because nobody controls it. Skipping when uncontrolled would incorrectly suppress the Forced.
- The controlling player never qualifies even if they have characters at the location.
- A non-controller with zero characters there never qualifies.

```php
$location = $theah->getCityLocation($this->Location);
foreach ($theah->game->loadPlayersBasicInfos() as $playerId => $_)
{
    if ($playerId == $location->Controller) continue;
    if (count($theah->getCharactersAtLocationByPlayerId($this->Location, $playerId)) === 0) continue;
    // qualify — e.g. queue createCardDrawnEvent
}
```

### "Each player has equal X" check

Iterate `$theah->game->loadPlayersBasicInfos()` (returns `[playerId => playerRow]`), build a flat array of per-player counts, then `count(array_unique($counts)) === 1`. Treats zero-zero as equal, which matches the literal text of cards like Equal Claim.

```php
$counts = [];
foreach ($theah->game->loadPlayersBasicInfos() as $playerId => $_)
{
    $counts[] = count($theah->getCharactersAtLocationByPlayerId($this->Location, $playerId));
}
if (count(array_unique($counts)) !== 1) return;
```

### Making a city location uncontrolled

Queue `EventFactory::createLocationBecomesUncontrolledEvent($playerId, $location)`. The hub handler in `EventHub.php` (≈ line 953) takes care of `setControllerForLocation`, the `Controller = 0` mutation on the in-memory `CityLocation`, and the `locationUncontrolled` notify. Do not call `setControllerForLocation` directly from a card.

The `$playerId` arg is the **current controller losing control**, not the triggerer (mirrors `Action_01130` / `Action_01112a`). For a Forced card, pull it from `$theah->getCityLocation($this->Location)->Controller`.

Gate the queue on `$location->isControlled()` — queuing the event when the location is already uncontrolled is wasted work and emits a confusing notify.

The hub's `locationUncontrolled` notify doesn't say *why*, so emit a card-attributed `${card_inject_code}: ...` notify on the card before queuing the event so the log reads top-down (see `_03cd08` for the convention).

### CityEventCard living in a player's Home

Some cards (e.g. `_03cd20` Early Morning Arrangements) move themselves from a city location into a specific player's Home as part of an effect, then become reactive while sitting there. The default `CityEventCard` data model fully supports this (`Location` is just a string, `ControllerId` is just an int), but three things have to line up or the card vanishes from the UI.

1. **`ControllerId` must be set to the target player BEFORE queueing the move event.** `EventCardMoved`'s handler calls `$deck->moveCard($card->Id, $event->toLocation, $card->ControllerId)` — the third arg becomes `card_location_arg` in the DB. For `LOCATION_PLAYER_HOME` that's the player id. Pattern:
   ```php
   $owner->ControllerId = $playerId;
   $owner->IsUpdated = true;   // re-serializes the card after the event loop tick
   $moveEvent = EventFactory::createCardMovingEvent(
       $playerId, $owner->Id, $owner->Location,
       Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id
   );
   $theah->queueEvent($moveEvent);
   ```
   `$theah->getCardById()` returns the same cached object inside the handler, so the freshly-set `ControllerId` is visible when `deck->moveCard` runs.

2. **The `cardMoved` client notify carries `controllerId`** (added to `EventHub.php`'s `EventCardMoved` handler). `notif_cardMoved` writes it back to `card.controllerId` before recomputing the destination, so `createCardId(card, PLAYER_HOME)` returns `${playerId}-${id}` and `getTargetElementForLocation(PLAYER_HOME, playerId)` resolves to that player's `home-anchor`. If you're moving a CityEventCard to home and the UI silently lands it under the wrong player (or nowhere), the controllerId-on-notify wire is the first thing to check.

3. **No JS template changes are needed.** `Utilities.js::createCard` already dispatches `card.type === 'Event'` to `createEventCard`, which places `jstpl_card_event` "before" the home-anchor endcap. `Setup.js`'s home-cards loop hits the same path on refresh because `getCardPropertiesAtLocation(LOCATION_PLAYER_HOME)` returns the card with the right `controllerId` and the filter at the top of the loop picks it up.

**Triggering "Reaction:" abilities while in Home.** The card's `Reaction` class checks `$owner->Location == Game::LOCATION_PLAYER_HOME` inside `handleEvent` and queues a `ReactionTransitionEvent` from there — exactly the same shape as a City Reaction but with a different location guard. `EventPhasePlanningEnd`, `EventDuskPhaseEnd`, etc. are the natural phase triggers. See `_03cd20` Reaction.

**Adjacency from Home is "all city locations".** `Theah::getAdjacentCityLocations(Game::LOCATION_PLAYER_HOME, $includeHome = false)` returns every city location available at the current player count (DOCKS / FORUM / BAZAAR / + OLES_INN at 3p / + GARDEN at 4p). When a card-in-Home effect says "move to an adjacent City location," that's the full city — not a restricted subset. Don't hand-write a different adjacency table.

**Going back out — discard from Home.** `createCardAddedToCityDiscardPileEvent($owner->ControllerId, $owner->Id, $owner->Location, $owner->Id, true)` works regardless of whether `$owner->Location` is a city or `LOCATION_PLAYER_HOME`. The hub handler clears `ControllerId` and `Engaged`, sets `Location = LOCATION_CITY_DISCARD`, and the `cardAddedToCityDiscardPile` notify destroys the DOM element — so the home-anchor cleans up automatically.

### Pressure-this-location City Action (with a stat)

For text like "**City Action:** Pressure this location with [Finesse/Combat/Influence/Resolve] • If successful, …", the action is an `EventCityAction` with `RequiresPerformerSelected = true` and no custom state. The pressure result flows back through the framework's `HIGH_DRAMA_PRESSURE_LOCATION` state and your `handleEvent` listens for the result.

Recipe (lifted from `Action_02014` / `Action_02035` / `Action_03cd20`):

```php
class Action_03cdNN extends EventCityAction
{
    public function __construct() {
        parent::__construct();
        $this->Name = clienttranslate("…short description…");
        $this->RequiresPerformerSelected = true;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array {
        $performers = parent::getPerformersForAction($playerId, $theah);   // friendly chars at this location
        return array_values(array_filter($performers, fn($p) => !$p->Engaged));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;
        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function handleEvent(Event $event) {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $game  = $event->theah->game;

            $game->globals->set(Game::PRESSURING_PLAYER, $event->playerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);    // pick the stat

            $performer = $game->theah->getCharacterById((int)$game->globals->get(Game::CHOSEN_PERFORMER));
            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_FINESSE);

            $event->theah->queueEvent(
                EventFactory::createPressureOccuringEvent($event->playerId, $performer->Id, $performer->Location, $pressureStats)
            );
            $event->theah->queueEvent(
                EventFactory::createTransitionEvent($event->playerId, $owner->Id, "pressureLocation", $this->Id)
            );
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id) {
            if ($event->success) {
                // …apply success effect…
            }
            $event->theah->queueEvent(EventFactory::createActionResolvedEvent($event->playerId));
        }
    }
}
```

Key points:

- The `"pressureLocation"` transition key is **already** registered globally in `states.inc.php` (under `HIGH_DRAMA_PLAYER_TURN_EVENTS`). You do NOT need to add it — and you do NOT need a custom state class for the pressure itself.
- Pass `$this->Id` as the transition's `$internalId`. The framework propagates it through the pressure flow and you get it back on `EventLocationPressureResult` as `$event->abilityId`. That's how you distinguish your card's pressure from somebody else's during the same High Drama turn.
- Set `PRESSURE_TYPE = NORMAL_PRESSURE_TYPE` (a fresh `set`, not `OR`) unless the card *also* changes how pressure is counted. Only call `setGlobalFlag(PRESSURE_TYPE, …)` if your card has a Forced clause like "count only en-garde characters" (see `_03cd08`, `_01184`).
- `getPressureStats($performer, $location, $stat)` is the canonical way to compute the stat array passed to `createPressureOccuringEvent`. Don't hand-roll it.
- `createActionResolvedEvent` always fires (success or failure) so the engine advances out of the action.
- Pressure engages the performer as part of resolution — that's why `getPerformersForAction` filters out engaged characters. Don't manually queue `createCardEngagedEvent`.

For the "If successful, add a city card to this location" follow-up, queue `createCityCardAddedToLocationEvent((int)$topCard['id'], $location)` using `getCardsOnTopOfCityDeck(1)` (see Penya `_03cd01::triggerForcedAbility` for the exact shape).
