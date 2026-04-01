# Aldo Bussotti (_01007) Audit

## Card Text
> Aldo gains +1 [Influence] for each Renown at this location.
> City Action: Move a Renown from a location you control to this one.

Vodacce character. Resolve=4, Combat=1, Finesse=3, Influence=1. Traits: Diplomat, Red Hand, Vodacce. Single city action (`Action_01007`).

## Bug Found and Fixed

### EventReknownRemovedFromLocation handler had inverted sign

In `_01007.php` line 91, the `EventReknownRemovedFromLocation` handler passed `-$location->Renown` to `updateInfluence`:

```php
$this->updateInfluence($event->theah, -$location->Renown);
```

WHY this is wrong: `EventReknownRemovedFromLocation` has `runEventHubAfterCards = false` (the default). This means the EventHub processes the event FIRST — decrementing `$location->Renown` — and THEN card handlers fire. So by the time `_01007::handleEvent` runs, `$location->Renown` already reflects the new total after removal.

`updateInfluence` sets NewInfluence = base (1) + count. Passing `-$location->Renown` gives `1 - newTotal`, which is completely wrong. Example: location had 3 renown, 1 removed → Renown is now 2 → code computed 1 + (-2) = -1 → clamped to 0. Should be 1 + 2 = 3.

The `EventReknownAddedToLocation` handler on line 85 does the same thing correctly — passes `$location->Renown` (positive) — so this was just a sign error on the removed handler.

Fixed by removing the negation: `$this->updateInfluence($event->theah, $location->Renown)`.

## Verified Correct

- **Passive influence tracking** (`EventCardMoved`): When Aldo moves to PLAYER_HOME, influence resets to base (1). When he moves to a city location, influence = base + location's renown. Both correct. `EventCardMoved` has `runEventHubAfterCards = true` (card handlers fire first), but this doesn't matter since we're reading the destination's renown which isn't changed by the move.
- **Passive influence tracking** (`EventReknownAddedToLocation`): When renown is added at Aldo's location, influence updates to base + new total. Correct — EventHub increments first, then this handler reads the updated value.
- **Action availability** (`Action_01007::isAvailableToPlayer`): Checks for controlled locations (excluding Aldo's current location) with Renown > 0. Matches "a location you control."
- **Action args** (`getArgsFromAction`): Returns same filtered locations for UI. Consistent.
- **Action execution** (`actFromActionWithIds`): Validates control and renown, removes 1 from chosen location, adds 1 to Aldo's location. Matches "Move a Renown from a location you control to this one." Events queued in correct order (remove then add).
- **State definition** (`State_highDramaPhase01007`): Active player state, location selection, proper transitions.
- **JS client**: Makes eligible city locations selectable, confirm button, cleanup on leave. Standard pattern.

## Note on event ordering within the action

When Aldo's action fires: remove event is at a different location (not Aldo's), so the `EventReknownRemovedFromLocation` handler doesn't trigger (location check fails). The add event IS at Aldo's location, so `EventReknownAddedToLocation` handler fires and correctly updates influence. No double-update issue.
