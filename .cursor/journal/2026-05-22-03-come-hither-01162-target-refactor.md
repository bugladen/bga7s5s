# Come Hither (01162) — Performer→Target refactor

## Context

`_01162` text: **"Action: Target a character • Move them to an adjacent City location."**

The pre-existing implementation conflated "target" with "performer":
- `Action_01162::RequiresPerformerSelected = true`
- `getPerformersForAction` returned `getCharactersInPlay()` (any character — friend OR foe), abusing the performer pick UI to do target selection.
- `isValidTargetForAbility` was a stub returning `[true, ""]` because the framework picked via the performer flow, never via target validation.
- No `EventCharacterTargeted` ever fired, despite the class implementing `IAbilityThatTargetsCharacters` and the card text literally saying "Target a character." Reactions like Vittoria (01014), Diplomatic Impunity (02016), and Maryam (01186) couldn't see/redirect/cancel a Come Hither.

User asked to switch `RequiresPerformerSelected` to `false` and insert a dedicated state for target selection.

## What changed

1. **New state layout** (mirrors the 01161 convention — first state picks the character, `_2` carries out the consequence):
   - `HIGH_DRAMA_PLAYER_TURN_01162` (renamed semantics) — active player picks the target character. `actFromCardWithId`.
   - `HIGH_DRAMA_PLAYER_TURN_01162_2` (new id 4011622) — active player picks the destination location. `actFromCardWithLocations`. This is the OLD 01162 behavior, just moved.

2. **Action_01162** rewritten:
   - `RequiresPerformerSelected = false`.
   - Dropped `getPerformersForAction` (no performer concept).
   - `handleEvent(EventActionTriggered)` now transitions to `"01162"` (target pick state).
   - `actFromActionWithId` (state 01162): validates target, stores `CHOSEN_TARGET`, **fires `EventCharacterTargeted`** so reactions can hook in, then queues a transition to `"01162_2"`.
   - `actFromActionWithIds` (state 01162_2): validates adjacency vs. `CHOSEN_TARGET`'s current location, moves them.
   - Added `EventCharacterTargeted` re-sync branch in `handleEvent`: if a reaction redirects (e.g. Vittoria swap), `CHOSEN_TARGET` follows the new `targetId`. Mirrors the pattern in `Action_01078.php` (Defending Honor).
   - `isAvailableToPlayer` now also requires at least one character in play.
   - `isValidTargetForAbility` still returns `[true, ""]` — the card text places no constraint on which character can be targeted.

3. **State files**:
   - `State_highDramaPhase01162.php` rewritten — now the target-pick state.
   - `State_highDramaPhase01162_2.php` created — the relocated location-pick state.

4. **States.php + states.inc.php**: added `01162_2` constant + transition table entry.

5. **JS** (`OnEnteringState.7s5s.js`, `OnLeavingState.7s5s.js`, `OnUpdateActionButtons.7s5s.js`): split the old `highDramaPhase01162` handler into `01162` (character select — highlights selectable cards, "Confirm Selection" button) and `01162_2` (location select — highlights adjacent city locations, "Confirm Location" button). Renamed the carried client arg `performerId` → `targetId` for accuracy.

## WHY decisions

**WHY rename to `_2` instead of adding a `_target` suffix:**
Followed the `01161 / 01161_2` precedent — first state is the unsuffixed one, sub-states are `_2`, `_3` etc. Keeping that convention means future maintainers can grep `01162` and immediately understand the flow order. The rename touches a few files but they're all in one logical area and the diff is mechanical.

**WHY fire `EventCharacterTargeted` from the new target-pick state:**
Per the 2026-05-18 audit journal, this event exists so target-redirect/cancel reactions can intercept "X targets a character" abilities. Come Hither qualifies: the source is a `Risk` that implements `IRiskThatTargetsCharacters`, and the action's text reads "Target a character." Maryam-style cancellation specifically requires this fire site to work. Adding it now closes a real gap rather than future-proofing.

**WHY also handle `EventCharacterTargeted` in our own handleEvent:**
If a reaction (Vittoria, DI) re-queues the targeted event with a different `targetId`, we need `CHOSEN_TARGET` to follow — otherwise the subsequent location pick will pull adjacency off the original character. Copied the sync pattern verbatim from `Action_01078.php:127-132`.

**WHY keep `IRiskThatTargetsCharacters` on `_01162.php`:**
That interface is the gate Maryam and Reaction_02048 use to identify Risks-that-target-characters. The text still says "Target a character," so the marker stays accurate.

**WHY no performer wound / no `getOwningCharacter()` logic:**
The card text is bare "Action:" not "City Action:" and includes no "your performer" clause. Nothing on the card touches a player-side character; the target IS the only character interaction. Memory entry "Action vs City Action performer scope" supports this read.

## Audit findings (kept here for posterity)

Pre-existing bugs the refactor incidentally fixes:
- Vittoria / DI / Maryam were silently ineligible to react to Come Hither because no `EventCharacterTargeted` fired.
- The "performer" UI hint was misleading — players who saw "Choose a performer" then realized they could pick any character (including opponents) had no in-game explanation of what was happening.
- `isValidTargetForAbility` returning `[true, ""]` was technically a stub left over from the 2026-03-07 interface-conformance pass; now it actually runs as part of the target-pick path even if its body is still trivially true. That's fine — the body would only get logic if the card text later constrained the target.

## Not done / open questions

- Did not add an automated test (no test runner exists in this repo).
- Have not visually verified in BGA Studio — would need to deploy and play. Mentioning so the next session knows to smoke-test: play Come Hither, expect a character-pick state, then a location-pick state with the chosen character highlighted, then the character moves.
- The double-trigger concern raised in the 2026-05-18 journal applies here too: when Come Hither moves a character, `EventCharacterTargeted` fires AND the subsequent `EventCardMoving` fires. If a reaction listens to both, it could double-trip. The `skipNextEvent` guards on existing reactions should absorb it, but worth watching.
