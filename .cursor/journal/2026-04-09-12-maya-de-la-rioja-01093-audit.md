# Maya de La Rioja (01093) Audit

Card text: "**Action:** Move Maya to an adjacent City location. If you are not the first player, she may move to any location instead. **Technique:** -1 Riposte • The adversary discards a card. (Your combat card must have at least 1 Riposte.)"

## Action: Move to adjacent/any location

Four bugs found and fixed in `Action_01093`:

### Bug 1: First player could move home (`getArgsFromAction`)
`getAdjacentCityLocations($performer->Location, $includeHome = true)` included home as a valid target. Card says "adjacent City location" — home is not a city location. Changed to `$includeHome = false`.

### Bug 2: Missing `OVERRIDE_AS_NOT_FIRST_PLAYER` in `actFromActionWithIds`
`getArgsFromAction` correctly checks the override global (from card 01090's reaction), but `actFromActionWithIds` used a bare `$game->globals->get(Game::FIRST_PLAYER) == $owner->ControllerId` without the override check. A player with the override active would see "any location" options in the UI but get rejected server-side for non-adjacent picks. Added the override check to match `getArgsFromAction`.

### Bug 3: `getCityLocation` crash for home (`actFromActionWithIds`)
`getCityLocation($ids[0])` throws an exception for `LOCATION_PLAYER_HOME` since home isn't in the city locations array. Non-first-player could select home (it's in their args) and crash the game. Rewrote to use the location name string directly instead of fetching a `CityLocation` object. Added proper validation for non-first-player: checks against all city locations plus home.

### Bug 4: Non-first-player at home saw home as move target (`getArgsFromAction`)
When Maya was at home and the player was not first player, home was unconditionally added to the location list even though she was already there. Added a check: only add home if Maya is not already at home. The "cannot move to same location" server validation would have caught it, but the UI would show a dead option.

## Technique: -1 Riposte, adversary discards

Two issues fixed in `Technique_01093`:

### Bug 5: No empty-hand check before discard state
`handleEvent` unconditionally created a transition to `DUEL_CHOOSE_TECHNIQUE_01093` for the adversary to discard. If the adversary had zero cards in hand, the state has no pass/skip option — deadlock. Added `count($hand) > 0` check before queueing the transition. The -1 riposte still applies regardless; only the discard is skipped.

### Bug 6: Redundant `getCardObjectFromDb` call
`$card = $game->getCardObjectFromDb($id)` was called on both line 65 and line 85. Removed the duplicate on line 85 since the `$card` variable was already populated.

## Verified correct
- `isAvailableToPlayer` on action: No city check needed (card says "Action:", not "City Action:"). Parent handles standard availability. Correct.
- `isAvailableToPlayer` on technique: Checks `$inDuel` and `$riposte > 0`. Card requires "at least 1 Riposte" — correct gate.
- `EventDuelCalculateTechniqueValues` handler: `-1 riposte`. Correct.
- `actFromTechniqueWithId`: Validates card exists, active player controls it, card in hand. Discard event uses `$card->OwnerId` (adversary) as initiator and Maya as source. Correct.
- State files: Both `State_highDramaPhase01093` and `State_duelChooseTechnique_01093` are correctly wired — right state types, transitions, and possible actions.
- `IAbilityThatDependsOnNotBeingFirstPlayer`: Marker interface. Allows card 01090's reaction to know this ability benefits from the override. Correctly implemented.
