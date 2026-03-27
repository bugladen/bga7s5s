# Patricia Moustakas (_01095) Audit

## Card Text
- **Passive:** While Patricia is at [The Docks], if she is en garde, she cannot be issued challenges.
- **City Action (a):** If Patricia is at [The Docks] • Claim it.
- **City Action (b):** If Patricia is at [The Docks], engage her • If you are the first player, draw a card. Otherwise, each opponent discards a card.

## Files Audited
- `modules/php/cards/_7s5s/_01095.php` (main class)
- `modules/php/cards/_7s5s/actions/Action_01095a.php` (claim docks action)
- `modules/php/cards/_7s5s/actions/Action_01095b.php` (draw/discard action)
- `modules/php/States/_7s5s/State_highDramaPhase01095.php` (multi-player discard state)

## Bugs Found & Fixed (all in Action_01095b.php)

### Bug 1: isAvailableToPlayer — wrong location check
The code used `cardInCity($patricia)` which allows the action at *any* city location, then only blocked if at Docks AND engaged. The card requires Patricia to be at **The Docks** specifically.

**Before:**
```php
if (! $theah->cardInCity($patricia)) { return false; }
if ($patricia->Location == Game::LOCATION_CITY_DOCKS && $patricia->Engaged) { return false; }
```

**After:**
```php
if ($patricia->Location != Game::LOCATION_CITY_DOCKS) { return false; }
if ($patricia->Engaged) { return false; }
```

WHY: The `cardInCity` check was too permissive — it returns true for any city location (Senate, Wharf, etc.), not just The Docks. The engaged check was also incorrectly scoped — only blocked when at Docks AND engaged, meaning at other city locations it would allow the action even if engaged. The fix makes both conditions explicit and independent.

### Bug 2: Missing engage event
The card text says "engage her" as a cost of using the action, but `handleEvent` never queued a `createCardEngagedEvent`. Per codebase patterns (Action_01029, Action_01030, Action_01105), each action must explicitly queue its own engage event.

Added before the first-player check so engagement happens regardless of which branch executes.

### Bug 3: Missing ActionResolvedEvent
Neither the first-player path (draw a card) nor the non-first-player path (transition to discard state) queued an `ActionResolvedEvent`. Compare with Action_01095a which does queue it, and Action_01131 which queues it after a transition event.

Added after `setUsed()` so it applies to both branches. For the non-first-player path, it'll be processed after the multi-player discard state finishes and control returns to the events state.

## Everything Else Is Correct

- **Passive ability (eventCheck):** Correctly blocks `EventChallengeIssued` when Patricia is at The Docks and en garde (`!$this->Engaged`). The `defenderId` check correctly identifies Patricia as the challenge target.
- **Action_01095a:** Correctly checks `Location == LOCATION_CITY_DOCKS`, claims location, queues ActionResolvedEvent. No engage cost on this action per card text.
- **State_highDramaPhase01095:** Multi-player state correctly uses `stMultiPlayerInitSansInitiatingPlayer` to exclude Patricia's controller from the discard. Transitions to `HIGH_DRAMA_PLAYER_TURN_EVENTS` when done.
- **actFromActionWithId (discard handler):** Correctly validates card is in player's hand, queues discard event, sets player non-multiactive.
