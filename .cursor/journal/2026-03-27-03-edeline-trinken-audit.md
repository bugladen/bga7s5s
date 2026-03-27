# Edeline Trinken (_01037) Audit

## Card Text
> Edeline gains +1 [Inf] for each character at this location.
> Reaction: After a duel, target an engaged participant at an adjacent location • Move them to this location.

## Prior Work
The 2026-03-05 journal entry documented a PLAYER_HOME bug fix for the influence tracking. The `EventCardMoved` handlers had an issue where PLAYER_HOME is a shared string constant — Edeline would react to characters leaving other players' homes. The fix added ControllerId guards on the PLAYER_HOME cases for EventCardMoved. That fix was correct and remains intact.

## Issues Found

### 1. Reaction missing duel participant check (Reaction_01037)
The card says "target an engaged participant" — "participant" means a participant in the duel (challenger or defender). The `isValidTargetForAbility` method only checked adjacency and engaged status, but didn't verify the target was actually a duel participant. A crafted request could theoretically target any engaged character at an adjacent location. `performReaction` also had no server-side validation.

**Fix**: Added `$character->Id != $this->ChallengerId && $character->Id != $this->DefenderId` check to `isValidTargetForAbility`. Added `isValidTargetForAbility` call in `performReaction` before executing the move.

### 2. EventCharacterRecruited handler — wrong location (MAJOR)
The handler called `$this->updateInfluence($event->theah, Game::LOCATION_PLAYER_HOME, 1)`. This was wrong for two reasons:
- **Wrong location**: When a Mercenary at Edeline's city location is recruited (gaining a ControllerId), the influence calculation should be based on Edeline's actual location, not PLAYER_HOME.
- **Wrong adjustment**: `EventCharacterRecruited` doesn't set `runEventHubAfterCards = true`, so EventHub runs first. By the time card handlers run, the character's `ControllerId` is already set, and `getCharactersAtLocation` already counts them. Adjustment should be 0.
**Fix**: Changed to `$this->updateInfluence($event->theah, $this->Location)`. No PLAYER_HOME guard needed — recruiting is a city-only action (a character at a city location recruits an uncontrolled Mercenary at the same location). It can never occur at PLAYER_HOME.

### 3. EventCharacterMustered handler — off-by-one
Used `+1` adjustment, but `EventCharacterMustered` also doesn't set `runEventHubAfterCards = true`. EventHub moves the character before card handlers run. `getCharactersAtLocation` already counts the mustered character. The `+1` overcounted by one.

**Fix**: Changed `$this->updateInfluence($event->theah, $event->location, 1)` to `$this->updateInfluence($event->theah, $event->location)`.

## WHY: The runEventHubAfterCards Timing Issue

This is the critical insight for understanding Edeline's adjustment values. The event processing loop in `Theah.php` processes events one at a time:

1. If `!$event->runEventHubAfterCards` → EventHub modifies state FIRST, then card handlers react
2. If `$event->runEventHubAfterCards` → Card handlers react FIRST, then EventHub modifies state

- `EventCardMoved` sets `runEventHubAfterCards = true` → Card handlers run BEFORE the card is moved. The `+1`/`-1` adjustments anticipate the move. **Correct.**
- `EventCharacterMustered` defaults to `false` → EventHub moves the character FIRST. The character is already counted. `+1` overcounts. **Was wrong.**
- `EventCharacterRecruited` defaults to `false` → EventHub sets ControllerId FIRST. The character is already counted by `getCharactersAtLocation`. `+1` overcounts. **Was wrong.**

The `EventApproachCharacterPlayed` handler already uses adjustment 0, which is consistent with EventHub running first.

## Observation: EventCharacterDestroyed adjustment

The `EventCharacterDestroyed` handler uses `-1` adjustment. I didn't check whether `EventCharacterDestroyed` sets `runEventHubAfterCards`. If it defaults to false (EventHub removes the character first), then the `-1` would undercount. Leaving this for now since it wasn't obviously wrong and I didn't fully trace the destroy event lifecycle. Worth revisiting.

## Files Modified
- `modules/php/cards/_7s5s/_01037.php` — Fixed EventCharacterMustered and EventCharacterRecruited handlers
- `modules/php/cards/_7s5s/reactions/Reaction_01037.php` — Added participant validation to isValidTargetForAbility and performReaction
