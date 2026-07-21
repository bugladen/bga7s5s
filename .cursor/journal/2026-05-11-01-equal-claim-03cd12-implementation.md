# Equal Claim (_03cd12) Implementation

City Event Card with a single Forced clause: "At the end of High Drama • If each player has an equal number of characters at this location, it becomes uncontrolled."

## Approach

Pure Pattern A (Forced) card — no Actions, no Reactions, no State classes, no JS wiring. Just an override of `handleEvent` on the card class.

Trigger conditions:
- `EventHighDramaPhaseEnd` — this event exists and is already dispatched centrally by `StatesTrait` at high drama end; `_01025_Burden` already uses it as a precedent for "end of high drama" handling.
- `cardInCity($this)` — the card must currently occupy a city location.
- `location->isControlled()` — the location must currently be controlled. If it's already uncontrolled, no transition happens, so skip the work and the noise.

Equality check:
- Iterate `loadPlayersBasicInfos()`, count `getCharactersAtLocationByPlayerId($this->Location, $playerId)` per player, dedupe with `array_unique`. One unique value ⇒ all equal.
- This treats zero-zero as "equal" — which matches the card text literally. Zero-zero only matters if the location is somehow controlled with no characters present (possible: control persists after characters leave). In that edge case, making it uncontrolled feels correct anyway.

Effect:
- Queue `EventFactory::createLocationBecomesUncontrolledEvent($location->Controller, $this->Location)`. The hub handler in `EventHub.php:953` already takes care of `setControllerForLocation`, the `Controller = 0` mutation, and the `locationUncontrolled` notification. Pass the current controller as `playerId` since that matches the existing usage pattern (e.g. `Action_01130` passes `$character->ControllerId`, the player losing control).

## Why a notify on the card itself

`EventLocationBecomesUncontrolled`'s handler already emits a `locationUncontrolled` message, but it doesn't say *why*. Without a card-attributed notify line, players see the location flip with no causal trail. Followed the convention from `_03cd08` (Inauguration Day) of emitting `${card_inject_code}: ...` before queuing the consequence so the log reads top-down.

## Considered alternatives

- Listening to `EventDuskEndOfDay` or `EventPhaseHighDrama` end — wrong granularity. The card says "end of High Drama," which is exactly `EventHighDramaPhaseEnd`.
- Checking only opposed players (i.e. excluding players with zero characters) — text doesn't allow this; "each player" is inclusive.
- Excluding the controller from the equality check (treating "controller has more than challenger" as the normal case) — doesn't match text either. The text really does say "equal" with no asymmetry.

## Pre-commit hook

No special pre-commit requirements for a pure Forced CityEventCard — none of the hook patterns (`Action`, `Reaction`, sorcerer ability, etc.) apply.
