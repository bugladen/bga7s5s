# Crash the Party (_02004) Audit

## Context

Continuing the card audit series (_02001, _02002, _02003). Eddie asked to audit _02004 (Crash the Party) against its card text. This is a Vodacce scheme with a City Reaction.

## Findings

### Bug 1: Missing opponent check on reaction trigger

The card says "When an **opponent** initiates a pressure" but the `handleEvent` in `Reaction_02004` only checks `$event instanceof EventPressureOccuring` — never compares `$event->playerId` against the owner's `ControllerId`. So the reaction fires on the player's OWN pressures too. Easy fix: add `$event->playerId == $owner->ControllerId` early return.

Checked `Reaction_01184` for comparison — it also uses `EventPressureOccuring` but its card text doesn't specify "opponent" so no check is needed there. The pattern exists in the event; the data (`$playerId`) is available, just not used.

### Bug 2: Card draw on pass

The `$this->location` field is set in `handleEvent` when `EventPressureOccuring` fires. It's used as a flag by the second handler (`EventLocationPressured`) to determine whether to draw a card. Problem: when the player passes, `performReaction` never clears `$this->location`. So the `EventLocationPressured` handler still sees `$this->location != ''` and draws a card on successful pressure — even though the player declined to use the reaction.

WHY this is definitely a bug: In the card game, declining a reaction means you don't get any of its effects. The move and the draw are both part of the reaction's effect block (after the bullet separator). Passing = no effects.

### Everything else is correct

The scheme's renown-adding is straightforward. The adjacency/en-garde filtering logic is solid. `$includeHome = false` is appropriate for city reactions.

## Pattern observation

The `$this->location` as a state flag between two event handlers is a pattern that could be fragile. Other reactions with similar two-phase logic should be checked to make sure they clean up properly on pass. Haven't seen this pattern in other reactions yet though — most are single-event.
