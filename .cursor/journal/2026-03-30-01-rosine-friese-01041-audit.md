# Rosine Friese (_01041) Audit

## Card Text
- Passive: "While Rosine is opposing a Sorcerer, she gains +1 [inf]."
- City Action: "Target an opposing non-Leader character with equal or lower [Influence] • Engage them. If they are a Sorcerer, move them Home."

## Event Timing (critical for understanding these handlers)
The passive relies on tracking when the opposing sorcerer count at Rosine's location transitions between 0 and >=1. The event timing model matters:
- `EventCardMoved`: `runEventHubAfterCards = true` → cards see old Location values
- `EventCharacterMustered`: `runEventHubAfterCards = false` → cards see already-updated Location/ControllerId
- `EventCharacterDestroyed`: `runEventHubAfterCards = true` → cards see character still at location
- `EventCharacterRecruited`: `runEventHubAfterCards = false` → cards see already-updated ControllerId

## Three Bugs Fixed

### 1. EventCharacterMustered (other character) — missing location + type checks
Was triggering +1 when ANY character was mustered at ANY location that happened to have exactly 1 opposing sorcerer. Fixed by adding: location match, sorcerer trait check, and opposing check.

### 2. EventCharacterDestroyed — missing sorcerer + opposing checks
Was triggering -1 when ANY character was destroyed at Rosine's location if there was exactly 1 opposing sorcerer. A non-sorcerer dying didn't change the sorcerer count but the buff was incorrectly removed. Fixed by checking the destroyed character is actually an opposing sorcerer.

### 3. EventCharacterRecruited — wrong count check
The old code checked count == 0 and applied +1. But since EventHub runs first for this event (runEventHubAfterCards = false), the recruited character's ControllerId is already updated. When the opponent recruits a sorcerer at Rosine's location, the character is already counted as opposing — so the count would be 1 (for the first opposing sorcerer), not 0. The old code would never trigger.

Initially I over-complicated the fix by adding a branch for "Rosine's player recruits an opposing sorcerer" — Eddie correctly pointed out that's an impossible scenario. Recruitment brings characters under your own control; you don't steal opponents' characters. Simplified to a single condition: opponent recruited a sorcerer at Rosine's location and it's the first opposing sorcerer (count == 1).

## Action_01041 (City Action) — Verified Correct
Targeting, validation, engage, and sorcerer-home-move all match the card text. State wiring is clean.

## The EventCardMoved handlers (lines 77-105) — Verified Correct
These correctly handle Rosine moving between locations and other characters arriving/leaving. The count checks account for event timing (card location not yet updated when handlers fire). The `fromLocation != LOCATION_PLAYER_HOME` exclusion on Rosine's own move is correct since mustering from home is handled separately by EventCharacterMustered.
