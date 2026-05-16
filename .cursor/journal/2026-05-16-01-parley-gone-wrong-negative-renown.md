# Parley Gone Wrong — Negative Renown at Governor's Garden

## Bug report
A game ended planning phase with `LOCATION_CITY_GOVERNORS_GARDEN` at **-2 Renown**. `_01150` (Parley Gone Wrong) had resolved first and multiple opponents apparently moved Renown out of Governor's Garden.

## Root cause — event queue ordering

`_01150::handleEvent` queues one `EventTransition` per opponent at `MEDIUM_PRIORITY` (`_01150.php:60-66`). Each transition routes the opponent to state `PLANNING_PHASE_RESOLVE_SCHEMES_01150`, where they pick a location via `actFromCardWithIds`. That handler then queued `EventReknownRemovedFromLocation` + `EventReknownAddedToLocation` *also at MEDIUM_PRIORITY* (the default in `Event.php:27`).

`DB.php:46` dequeues purely by `event_priority`. Within the same priority it's FIFO by `event_id`. And `Theah.php:262-288` shows that processing an `EventTransition` returns from `runEvents` immediately.

So with opponents B, C, D and Garden starting at 1:

1. Resolution queues `[add(Forum,+1), transition(B), transition(C), transition(D)]`.
2. `transition(B)` fires → B enters state, picks Garden (UI shows 1), queues `remove(Garden), add(Forum)` at MEDIUM.
   - Queue: `[transition(C), transition(D), remove(B,Garden), add(B,Forum)]`
3. `transition(C)` fires *before B's remove runs*. C's chip still shows Garden=1. C picks Garden too.
4. Same for D.
5. Three `remove(Garden)` events finally fire back-to-back: 1 - 1 - 1 - 1 = **-2**.

## Why the JS filter didn't help
`OnEnteringState.7s5s.js:337-351` correctly skips locations where the chip reads 0. But the chip is only updated by the `reknownRemovedFromLocation` notification, which doesn't fire until the corresponding event is dequeued — which happens *after* all the transitions have already run. The JS was checking accurate data; the data just hadn't been updated yet.

## No server-side guard
`EventHub.php:1201` (the `EventReknownRemovedFromLocation` handler) unconditionally subtracted. Unlike `EventReknownRemovedFromCard` at `:1099-1105` which clamps at 0. So once the events finally ran, nothing stopped Renown from going negative.

## Fix

**Two changes**, picked after weighing alternatives with the user:

### 1. Raise priority of the opponent's remove/add events (`_01150.php:135-142`)

```php
$removeEvent->priority = Event::HIGH_PRIORITY;
$addEvent->priority   = Event::HIGH_PRIORITY;
```

HIGH (2) beats MEDIUM (3), so after opponent B picks, the remove and add fire *before* the next opponent's transition is dequeued. Opponent C then enters state 01150 with a fresh chip — the existing JS filter handles the rest correctly.

WHY this over a server-side validation in `actFromCardWithIds`:
- A server validation would reject B's pick *after* B clicked. From B's perspective Garden looked fully valid (chip showed 1) — UX is bad: "the UI lied to me."
- The real bug is staleness, not lack of validation. Making the data fresh lets the existing client logic work as intended.

### 2. Clamp at 0 in the EventHub handler (`EventHub.php:1201-1217`)

```php
$reknown = max(0, $this->game->getRenownForLocation($event->location) - $event->amount);
$this->game->setReknownForLocation($event->location, $reknown);
$this->cityLocations[$event->location]->Renown = $reknown;
```

WHY: Defense in depth. The priority fix solves *this* card. But the underlying footgun — "any card that queues `EventReknownRemovedFromLocation` from a multi-opponent fan-out" — is still latent. Clamping matches the existing card-renown behavior at `:1104-1105` and ensures impossible states (negative location Renown) can't be reached.

Note: I changed the cached `cityLocations[...]->Renown` assignment from `-= amount` to `= reknown` so the in-memory value matches the clamped DB value rather than drifting if the DB clamps but the cache doesn't.

## Alternatives considered & rejected

- **Server-side check in `_01150::actFromCardWithIds` only.** Patches the one card; doesn't fix root cause; produces "UI says valid, server rejects" UX.
- **Only the clamp, no priority change.** Stops negatives, but each opponent would still happily pick the same location and only the first move would have any effect — silent no-ops, terrible UX.
- **Make ALL location renown events HIGH priority globally.** Too broad; could reorder unrelated interactions. Scoped the priority change to just the events queued from `_01150`.

## Future-self warning

If a future session sees `Event::HIGH_PRIORITY` on those two events in `_01150::actFromCardWithIds` and thinks "this is inconsistent with how other cards queue renown events, let me normalize" — **don't**. The priority bump is what prevents the staleness bug. Other cards that fan out an effect across multiple opponents based on shared state should probably do the same.

If the framework grows a generic "fan-out to opponents" helper, that helper should bake in the priority ordering so this trap isn't reachable by future card authors.
