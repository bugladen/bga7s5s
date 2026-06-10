# Odette Dubois D'Arrent (_03027) Implementation

## Card

**Odette Dubois D'Arrent — Disillusioned Courtier (Montaigne, Hero/Diplomat/Spy)**
Resolve 3, Combat 0, Finesse 3, Influence 3.

Two City Reactions:
1. **After another character at this location is destroyed •** Odette heals a wound. Then, you may move an adjacent Renown to this location.
2. **After a challenge is issued at this location •** Move your adjacent **Duelist** to this location. *(Before choosing to intervene.)*

## Plan

Both reactions are button-based (no state classes, no `states.inc.php` edits, no JS wiring), since each is a single-step choice off a prompt.

Split into two reaction files (`Reaction_03027a`, `Reaction_03027b`) because the framework's reaction prompt is per-reaction-class — letting each fire independently. This is the same shape Yevgeni (`_01116`) uses for paired reactions on one Leader and Ise (`_03016`) uses for the Dusk-opt-out + enemy-moved-here pair.

## Reaction A: destroyed → heal + optional renown

Trigger event: `EventCharacterDestroyed`.

Identity/scope gates (in this order; cheap first):
- `isAvailable()`
- `! characterIsInDiscardOrLocker($owner)`
- `cardInCity($owner)` — City Reaction
- `$event->characterId != $owner->Id` — "another character"
- destroyed character's `Location == $owner->Location` (same location)
- **Useful-effect precondition**: Odette has wounds OR an adjacent location has Renown. Without this, the prompt offers nothing.

**WHY `$destroyed->Location` is readable at this event time.** `EventCharacterDestroyed.runEventHubAfterCards = true`, so when `handleEvent` runs the destroyed character is still in memory with its old `Location` set. The EventHub moves the card to the locker AFTER all card handlers run. Same trick `Reaction_01013` and `_03026` use.

Button shape:
- One `moveFrom-<location>` button per adjacent city location with renown > 0.
- One `healOnly` button — always present, so the player can heal-and-skip-renown deliberately.
- Framework's Decline button = don't react at all (heal isn't paid).

**WHY the heal is queued unconditionally inside `performReaction`.** The card text reads "heals a wound. Then, you may …" — the heal is mandatory when the reaction triggers, the renown move is the optional second half. So heal goes through on *any* of my buttons (including "Heal only"). Framework Decline skips the whole reaction so no heal in that path. The engine clamps the heal at her actual `Wounds`, so it's harmless if she's at 0 wounds and they accept anyway (rare, would only happen if she had no wounds but did want the renown move — which is the whole point of `healOnly` being a no-op heal in that case).

Renown move: copied from `Reaction_01062` (the existing Odette Leader's "move adjacent renown to my location" reaction). Three events with the same `batchId`:
1. `createRenownMovingBetweenLocationsEvent` (the umbrella move event for animation/eventCheck)
2. `createRenownRemovedFromLocationEvent` (decrement source)
3. `createRenownAddedToLocationEvent` with `$isMove = true` (increment dest)

The batchId lets the engine group them as one logical move for log/UI purposes.

## Reaction B: challenge issued at this location → move adjacent Duelist

Trigger event: `EventChallengeIssued`.

**WHY `EventChallengeIssued` rather than e.g. `EventChallengeAccepted`.** The card text says "(Before choosing to intervene.)" — so the trigger must fire *before* the intervention window. `EventChallengeIssued` is queued in `StatesTrait::stIssueChallenge` BEFORE the state advances into the intervention dispatcher. The reaction transition gets queued onto the event queue, which the framework resolves before moving on to the next state — so the move-Duelist prompt fires before the defender's intervention prompt.

Compare: `EventChallengeAccepted` fires AFTER intervention is resolved (existing Odette `_01062`'s renown reaction uses it). Wrong timing for our text.

Identity/scope gates:
- `EventChallengeIssued` instance + `! canceled`
- `isAvailable()` + not in locker/discard + `cardInCity($owner)`
- `challenger.Location == $owner->Location` — "at this location"
- At least one eligible Duelist (controller match + Duelist trait + at an adjacent city location)

Button shape: one `move-<characterId>` button per eligible Duelist. Framework Decline = skip.

`performReaction` re-validates: controller match, Duelist trait, location is still adjacent. The location re-check matters because between the reaction prompt and the player click, in principle the character could have moved (chained reactions, etc.). Cheap guard.

The move itself is `createCardMovingEvent($controllerId, $cardId, $fromLocation, $toLocation, engage=false, $owner->Id, $this->Id)` — same shape as `Reaction_03016b` (Ise's pull-a-friendly).

## Why not `IAbilityThatTargetsCharacters`

The skill file (and `feedback_targets_characters_interface.md`) say: implement ONLY when the text uses the word "target". Reaction A wounds nobody and Reaction B moves a friendly character — neither text uses "target". Don't add the interface; other cards' "before being targeted" hooks should NOT see these.

## Pre-commit hook

`extends CardReaction` requires `$this->setUsed(` and `$this->isAvailable(` literals in each subclass. Both files have both. No `ISorcererAbility`, no `IAbilityThatTargetsCharacters`/`IAbilityThatTargetsCards` pair, no actions — no further hook checks apply.

## Files

- Created: `modules/php/cards/faf/reactions/Reaction_03027a.php`
- Created: `modules/php/cards/faf/reactions/Reaction_03027b.php`
- Modified: `modules/php/cards/faf/_03027.php` — added `IHasReactions`, `ReactionTrait`, and `$this->Reactions = [...]` wiring.

No `States.php`/`states.inc.php`/JS changes needed (button-based reactions).

## QA notes for future sessions

- Reaction A self-exclusion: confirm Odette herself dying doesn't trigger her own reaction.
- Reaction A useful-effect precondition: when Odette is at full health and no adjacent renown exists, the prompt should NOT fire.
- Reaction A "Heal only" button: clicking it should heal but not move renown.
- Reaction B "Before choosing to intervene": confirm the Duelist arrives at Odette's location *before* the intervention prompt opens (i.e., the moved Duelist should themselves be eligible to intervene if appropriate).
- Reaction B non-combat challenges: the trigger should fire for Influence/Finesse challenges too (the text doesn't restrict to combat).
- Both: once-per-day setUsed discipline; reset on `EventDuskEndOfDay` via the base class.
