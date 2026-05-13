# Cesca del Rosso (`_03001`) — implementation

Leader, Donna Sinistra, Vodacce Strega Sorcerer Villain. First Leader card
implemented for the faf expansion.

## Card text

- At the end of Dawn, draw five cards.
- **City Reaction:** After Cesca performs a **Sorcerer** ability • Wound an
  opposing character.
- **City Action:** Target an opposing non-Leader • Move a wound from your
  Strega at this location to that character.

## End-of-Dawn draw five

Wired via `EventPhaseDawnEnding` in `_03001::handleEvent`. The event is
already fired by `StatesTrait::stDawnEnding`, so no new plumbing is needed.

Guarded with `characterIsInDiscardOrLocker($this)` so a destroyed leader
doesn't draw. WHY: leaders technically still have a `ControllerId` after
destruction (it's used for the renown-loss calculation in `Leader::handleEvent`
on `EventCharacterDestroyed`), so the `$this->ControllerId > 0` check alone
is insufficient. The location check distinguishes "in play" from "dead." I
copied the helper rather than inventing a new "is in play" check.

Notify is sent before queueing the events so the message lands first in the
log. The five `createCardDrawnEvent` calls all carry the same inject code so
the log links back to Cesca.

## City Reaction — `Reaction_03001`

Pattern source: `Reaction_01118` (Elina). Same shape — trigger on
`EventSorcererAbilityPlayed` where `sourceId == owningCharacter->Id ||
performerId == owningCharacter->Id`, then `createReactionTransitionEvent`.

Differences:

1. **Wound-an-opposing-character target picker.** Used the button-per-target
   pattern from `Reaction_01016`. Each candidate character gets a
   `wound-<id>` button. WHY this over a dedicated state: reactions in this
   codebase don't get their own GameState files — they go through the
   shared `playerReaction` state and resolve via `getReactionButtonProperties`
   + `performReaction`. Cheap, fits the framework, no new state plumbing.

2. **"Opposing character" filter.** Memory note: opposing = different
   controller AND same location. So I filter by `isNotControlledByPlayer`
   AND `Location == cesca->Location`. `isNotControlledByPlayer` also
   excludes uncontrolled (`ControllerId == 0`) characters, which is the
   right thing.

3. **Not marked as `ISorcererAbility`.** The card text says "City Reaction"
   not "Sorcerer City Reaction." If it were a Sorcerer ability, the
   wound-on-resolve would re-trigger this same reaction in an infinite loop
   (since `EventSorcererAbilityPlayed` would fire from the reaction itself).
   Andriana (`Reaction_02001`) explicitly *is* a Sorcerer Reaction and gets
   away with it because the loop is broken by `setUsed` (once per day) —
   but it's still safer to follow the card text literally. Wounding is the
   effect of the sorcery Cesca just performed (which already fired the
   triggering event); the reaction itself is not the sorcery.

4. **Once per day via `setUsed`.** Default for CardReaction; cleared at
   `EventDuskEndOfDay` by the base class.

## City Action — `Action_03001`

Two-step CharacterAction. WHY two steps instead of one: the player must pick
two characters — a source Strega and a target opposing non-Leader. The
existing two-step pattern (e.g. `Action_03cd01`, `Action_02010`) handles
this cleanly with two state files.

**Step 1 (`HIGH_DRAMA_PLAYER_TURN_03001`):** Pick a Strega you control at
Cesca's location with `Wounds > 0`. Stored in `Game::CHOSEN_CARD`.

**Step 2 (`HIGH_DRAMA_PLAYER_TURN_03001_2`):** Pick an opposing non-Leader
at Cesca's location. Stored in `Game::CHOSEN_TARGET` implicitly via the
final wound/heal events.

WHY pick Strega first then target (not the reverse): matches
`Action_02010`'s order (the "Twist of the Arcana" sorcery does the same
wound-move). The card text reads "target opposing • move wound from Strega"
so target-first would also be defensible, but Strega-first gives the player
immediate feedback ("yes, you have a valid source") before they commit to a
target.

**Self-target source.** Cesca herself has the Strega trait. If she's
wounded, she shows up as a valid source. This is correct per the rules —
"your Strega at this location" includes Cesca.

**`isValidTargetForAbility`.** Implemented because the class also
implements `IAbilityThatTargetsCharacters`. Used in step 2 to validate the
target. WHY implement that interface: lets other cards' "before being
targeted" reactions/forced hooks fire correctly when Cesca's City Action
selects them. (E.g. Silver Spine's cancel on opponent's targeting.)

Wait — re-reading: Silver Spine only triggers on **Risk** abilities. So
that example doesn't apply. But other Targets-Characters hooks exist
(e.g. forced abilities that fire on "would be targeted by a sorcerer ability").
The interface is correct for surfacing the targeting.

**Not marked as `ISorcererAbility`.** Same reasoning as the City Reaction —
the card text doesn't say "Sorcerer City Action." This matters because:
- If it were a Sorcerer ability, performing it would fire the City Reaction
  immediately (since `EventSorcererAbilityPlayed` would fire from this
  action). The player could chain: action wounds target A, then reaction
  wounds target B. That's a meaningful gameplay difference and the card
  text doesn't authorize it.
- This is a delicate call. If a future rules clarification says moving a
  wound *is* sorcery (because it's manipulating fate strands), the
  interface should be added and the chain should be allowed. Flagging here
  in case QA disagrees.

**No `setUsed` / `resetPlayerPassCount` / `announceAction` calls.** Per
CLAUDE.md, those are no longer called from CharacterAction subclasses —
they run centrally during action confirmation.

**`createActionResolvedEvent`.** Called in step 2 after heal+wound. WHY in
step 2 and not step 1: the action isn't *resolved* until both choices are
made and effects applied. Step 1 is just a state transition.

## State IDs

I initially used `4031001` / `40310012` to dodge a potential collision with
a future `03CD12` state ID (which would naively encode as `4030012` — same
as `03001_2`). User overrode: use the straightforward `4` + cardId scheme
even if it could theoretically collide with a future CD card. Final:

- `HIGH_DRAMA_PLAYER_TURN_03001 = 403001`
- `HIGH_DRAMA_PLAYER_TURN_03001_2 = 4030012`

If a CD12 ever wants the same number, that conflict gets resolved then.
Saved as memory `feedback_state_id_encoding.md` so future agents don't
re-invent the "safety prefix" approach.

## Files touched

- `modules/php/cards/faf/_03001.php` — wired traits/interfaces, added
  EndOfDawn handler, registered Action/Reaction.
- `modules/php/cards/faf/actions/Action_03001.php` — new.
- `modules/php/cards/faf/reactions/Reaction_03001.php` — new.
- `modules/php/States/faf/State_highDramaPhase03001.php` — new state file
  for step 1.
- `modules/php/States/faf/State_highDramaPhase03001_2.php` — new state
  file for step 2.
- `modules/php/States.php` — added two constants (4031001, 40310012).
- `states.inc.php` — added one transition-name mapping (`"03001"`).
  Originally added `"03001_2"` too, mimicking the existing `03cd01_2`
  entry — but on review that entry is dead in both cases. The lookup is
  only consulted by `EventFactory::createTransitionEvent($playerId,
  $cardId, $transitionName, ...)`, and neither Penya nor Cesca call it
  with the `_2` suffix. State 1 → state 2 happens via
  `nextState("stregaChosen")` using the state's own transitions array.
  The only place `"<card>_2"` is actually used as a createTransitionEvent
  name is `Action_03cd03::handleTargetChosen`, which rotates through
  opponents by queueing transitions directly into the muster state.
  **Lesson:** don't copy the `_2` lookup line by default — only add it
  if the action actually calls createTransitionEvent with that name.
- `modules/js/OnEnteringState.faf.js` — added selectable highlighting for
  both states.
- `modules/js/OnUpdateActionButtons.faf.js` — added Confirm button for
  both states.
- `modules/js/OnLeavingState.faf.js` — added cleanup for both states.

## Pre-commit hook compliance

- `Action_03001 extends CharacterAction`: the hook's regex only matches
  `extends (CardAction|RiskAction|RiskCityAction)` directly, so the
  `createActionResolvedEvent` check doesn't fire on this class — but I
  call it anyway because CLAUDE.md says CharacterAction subclasses also
  need it.
- `Reaction_03001 extends CardReaction`: hook requires `$this->setUsed(`
  and `$this->isAvailable(` — both present.
- No `ISorcererAbility` (no start/played event requirement).
- Doesn't implement both Characters/Cards ability interfaces.

## Open questions / risks

- **Self-targeting in the City Reaction.** Cesca is a Sorcerer. When she
  performs *any* sorcerer ability, the reaction fires. If she's the only
  Strega/Sorcerer at a location with no opposing characters, the reaction
  is correctly skipped (count of targets == 0). Verified in `handleEvent`.

- **The City Action's IAbilityThatTargetsCharacters interface.** I added
  it for correctness, but the existing two-step CharacterAction examples
  (`Action_03cd01`, `Action_01076`) don't implement it. They get away with
  it because their targets aren't *characters being targeted by an
  ability* in the rules sense — they're "move my own character," "wound
  my own character." Cesca's target *is* an opposing character being
  hostilely targeted, which is the canonical IAbilityThatTargetsCharacters
  case. So the interface is appropriate here even if not in the other
  examples.

- **Edge case: zero opposing characters when the reaction would fire.**
  `getOpposingCharactersAtLocation` returns empty → handleEvent skips the
  transition. Player gets no UI prompt, which is correct (mandatory miss).

- **Edge case: Strega heals to 0 wounds between step 1 and step 2.** I
  re-validate the source's Wounds in step 2 and throw a UserException if
  invalid. Unlikely in practice but defensive.

- **No "Back" transition on step 2.** If the player picks a Strega and
  then wants to abandon the action, they have to use the framework's
  abort. Consider adding a back transition like `03cd01_2` has if QA
  flags this.

- **JS state cleanup on leave.** Mirrors `03cd01` — unhighlightCharacterChosen
  for the performer, unhighlightCards for the selectable set, and remove
  the `_7sfs-chosen` class from the source on step 2's leave. Tested the
  shape against the existing Penya implementation.
