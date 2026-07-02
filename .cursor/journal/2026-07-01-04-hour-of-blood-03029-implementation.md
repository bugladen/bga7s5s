# Hour of Blood (_03029) Implementation

## What the card does
- **Resolve:** Add Renown to City Forum + City Docks (trivial, no sub-state)
- **Sorcerer City Action:** Wound performer, then choose one Porté-style move:
  - A: move your character from anywhere → performer's location
  - B: move your character at performer's location → any city location or Home

## Design choices

**Three-state action flow (03029 → 03029_2 → optional 03029_3):**
Mirrors faf multi-step pattern (03026) and 01044 choose-then-pick. Option choice is button-driven in state 1; character pick in state 2; location pick only for option B in state 3. WHY: "Choose one: Either… or…" is a branch before target selection — can't use a single character-picker like Porté Travel (01085) which only has one mode.

**MoveMode persisted on Action_03029 ($MoveMode + $owner->IsUpdated):**
Not CHOSEN_TARGET — that's consumed by challenge framework. CHOSEN_CARD holds the character between steps 2→3 for option B.

**"Any location" for option B destinations:**
Used Maya (01093 non-first-player) pattern: all city locations + Home, excluding character's current location. Same as other "any location" character moves in codebase.

**Option A source pool:**
getCharactersInPlayByPlayerId filtered to Location != performer.Location — includes Home, matching "at any location".

**Sorcerer performer gate:**
getPerformersForAction filters hasTrait("Sorcerer"). Availability requires at least one Sorcerer performer with a valid option A or B.

**Event order in resolveMove:**
Sorcerer start → wound performer → move → sorcerer played → action resolved. Matches 01068/01085 Porté patterns.

**Did NOT override SchemeAction isAvailableToPlayer:**
Unlike 02045 (scheme placed on city location), normal schemes use base SchemeCityAction availability. Same as 01044/03005.

## Files touched
- `_03029.php` — resolve + IHasActions
- `actions/Action_03029.php`
- `States/faf/State_highDramaPhase03029{,_2,_3}.php`
- `States.php`, `states.inc.php`
- `OnEnteringState/OnUpdateActionButtons/OnLeavingState.faf.js`

## Unfinished / test notes
- Not in StarterDecks yet (card file was untracked scaffold)
- Manual test: planning resolve renown; HD Sorcerer city action both branches; back button between steps; zombie on each state

## Skill update (same session)
Fed learnings into create-scheme: Pattern D (branch-first HD action), HD vs planning transition tables (40 vs 26), Porté move pool helpers, _03029 walkthrough, SchemeCityAction availability nuance vs _02045.
