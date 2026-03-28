# Siren's Scream (_01179) Audit

## Card Text
> Forced: When this card is revealed • Each player spends a Renown to it.
>
> This card can only be discarded if it has no Renown. (Even during Dusk.)
>
> City Action: Engage your performer • Take a Renown from this card. (Each player may activate this ability once per Day.)

## Files Audited
- `modules/php/cards/_7s5s/_01179.php` (main class — CityEventCard)
- `modules/php/cards/_7s5s/actions/Action_01179.php` (City Action implementation)
- `modules/php/cards/CityEventCard.php` (parent class)
- `modules/php/cards/actions/EventCityAction.php` (parent action class)
- `modules/php/cards/actions/CardAction.php` (grandparent action class)
- `modules/php/cards/actions/Action.php` (base action class)
- `modules/php/cards/CardAbilityTrait.php` (OwnerId, Used, setUsed)
- `modules/php/theah/EventHub.php` (EventReknownAddedToCard, EventReknownRemovedFromCard, EventCardAddedToCityDiscardPile handlers)
- `modules/php/theah/Theah.php` (eventCheck / queueEvent flow)
- `modules/php/EventFactory.php` (createReknownRemovedFromCardEvent)
- `modules/php/StatesTrait.php` (stDuskPhaseCleanup, stDuskEndOfDay)
- `modules/php/Game.php` (getAllDatas for sirensScreamUsedList)

## Verdict: All Correct — No Bugs

### Forced Reveal Effect
**"When this card is revealed • Each player spends a Renown to it."**
- Triggers on `EventCityCardAddedToLocation` where `cardId == $this->Id`. ✅
- Iterates all players via `loadPlayersBasicInfos()`. ✅
- Checks each player's Renown > 0 before spending — correct, can't spend what you don't have. ✅
- Queues `EventPlayerLosesReknown` (amount 1) + `EventReknownAddedToCard` (amount 1, cardId = this card) per eligible player. ✅

### Discard Restriction
**"This card can only be discarded if it has no Renown. (Even during Dusk.)"**
- `eventCheck` catches `EventCardAddedToCityDiscardPile` where `cardId == $this->Id` and `Reknown > 0`, throws `BgaUserException`. ✅
- `Theah::queueEvent()` wraps `eventCheck()` in try/catch — exception prevents the event from being queued, card stays in play. ✅
- Dusk cleanup (`stDuskPhaseCleanup`) discards uncontrolled city cards via `createCardAddedToCityDiscardPileEvent` → goes through `queueEvent()` → eventCheck blocks it when Renown > 0. ✅
- Once all Renown is removed (via City Action), `Reknown == 0`, eventCheck passes, card discards normally at next Dusk. ✅

### City Action
**"Engage your performer • Take a Renown from this card. (Each player may activate this ability once per Day.)"**
- `RequiresPerformerSelected = true` — requires performer selection. ✅
- `EventCityAction::getPerformersForAction` filters to player's characters at card's location. ✅
- `Action_01179::getPerformersForAction` further filters to unengaged performers. ✅
- `isAvailableToPlayer` checks: parent chain (location, ownership), `card->Reknown == 0` returns false (no Renown to take), `in_array($playerId, playersUsed)` returns false (already used today). ✅
- `eventCheck` on `EventActionTriggered` double-checks once-per-day restriction. ✅
- `handleEvent` on `EventActionTriggered`: engages performer, removes 1 Renown from card, gives 1 Renown to player, queues action resolved. ✅
- `setUsed()` intentionally NOT called — global `Used` flag would prevent all players after the first; per-player tracking via `playersUsed[]` is correct for "each player once per Day." ✅
- `handleEvent` on `EventDuskEndOfDay`: clears `playersUsed[]`, resets for new Day. ✅

### Reknown Tracking
- `Reknown` property is on base `Card` class. EventHub handlers for `EventReknownAddedToCard` and `EventReknownRemovedFromCard` update `card->Reknown` and send notifications. ✅
- `EventReknownRemovedFromCard` handler clamps to 0 (`if ($card->Reknown < 0) $card->Reknown = 0`). ✅

### Page Refresh Support
- `Game::getAllDatas` searches `getAllCards()` for `_01179` instances, checks `cardInCity($card)`, includes `sirensScreamUsedList`. ✅
