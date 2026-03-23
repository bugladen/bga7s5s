# Edeline Trinken PLAYER_HOME Influence Bug Fix

## The Bug

Edeline Trinken (`_01037`) tracks influence based on the number of characters at her location. In `handleEvent`, when a card is moved away from her location, she reduces influence by 1. The problem: `PLAYER_HOME` is a shared string constant used for all players' home locations. So when *any* player moved a character from their home, Edeline's handler matched `$event->fromLocation == $this->Location` even if Edeline was at a completely different player's home.

This meant Edeline's influence would erroneously decrease when characters left other players' homes.

## The Fix

Added a guard on the `fromLocation` check: if the from-location is `PLAYER_HOME`, additionally verify that the moved card's `ControllerId` matches Edeline's `ControllerId`. A character at PLAYER_HOME should always be controlled by the player whose home it's at, so this correctly scopes the check to Edeline's home only.

Used `$event->theah->getCardById($event->cardId)->ControllerId` — this pattern is used elsewhere in the codebase (e.g., `_01063` line 127, `_01067` line 116).

## WHY this approach over alternatives

- Other cards (`_01063`, `_01067`) handle this by simply excluding `PLAYER_HOME` entirely (`$event->fromLocation != Game::LOCATION_PLAYER_HOME`). But Edeline *does* care about characters leaving her own home — she just shouldn't react to other players' homes.
- Considered using `$event->initiatingPlayerId` but that represents who triggered the move, not whose home the card was at. If Player A uses an ability to move Player B's character from Player B's home, `initiatingPlayerId` would be A, not B. The card's `ControllerId` is the correct field.

## Second Pass — toLocation fix

Confirmed the `toLocation` case (original line 76) had the same bug. If Edeline is at PLAYER_HOME and an opponent moves a card to their own PLAYER_HOME, the string match triggers and erroneously increases Edeline's influence.

Split the original combined condition `($event->cardId == $this->Id || $event->toLocation == $this->Location)` into two separate blocks:
1. `$event->cardId == $this->Id` — unconditional (Edeline herself being moved always matters)
2. `$event->cardId != $this->Id && $event->toLocation == $this->Location` — with the same PLAYER_HOME guard

The `cardId != this->Id` on block 2 makes the blocks mutually exclusive, so no double-firing risk. The original code's degenerate case (Edeline moves to her own location) is handled by block 1 only, which is correct.
