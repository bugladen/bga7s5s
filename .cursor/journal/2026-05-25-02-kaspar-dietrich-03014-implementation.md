# Kaspar Dietrich (03014) "Iron Reforged" — Implementation

## Card Text
- **Passive:** Opponents' abilities cannot wound or move wounds to Kaspar.
  *(Threat is still converted to wounds.)*
- **Technique:** If Kaspar is equipped with an **Eisenfaust** attachment or
  there is an **Eisenfaust** card in his dueling line • Wound the adversary.

## Files
- `modules/php/cards/faf/_03014.php` — passive + technique registration
- `modules/php/cards/faf/techniques/Technique_03014.php` — technique

## Passive — design choice: eventCheck on EventCharacterBeingWounded

I picked `eventCheck` (not `handleEvent`) and the *Being*-tense event (not the
already-resolved `EventCharacterWounded`) because:

1. **Setting `wounds = 0` in `eventCheck` cancels the entire follow-on chain.**
   `EventHub::handleEvent`'s `EventCharacterBeingWounded` handler only emits
   the post-tense `EventCharacterWounded` when `$event->wounds > 0` (see
   EventHub.php ~1988). Zero-wounds = no `EventCharacterWounded`, so nothing
   downstream (other reactions/passives that hook the past-tense event) thinks
   Kaspar took a wound. Cleaner than Maxime's pattern of skipping
   `parent::handleEvent`, which still lets the event propagate to other
   `Character::handleEvent` recipients.

2. **Mirrors Breastplate (`_01153::eventCheck`)** — the canonical block-wounds
   pattern. Breastplate reduces by 1; Kaspar zeroes out.

### Distinguishing "opponent's ability" from threat conversion

`StatesTrait` line ~1500 (the round-end threat-to-wounds conversion) emits
`EventCharacterBeingWounded` with **sourceId = the adversary character's Id**
and **abilityId = ''** (no ability). So the filter is:

- `abilityId == ''` → not an ability (threat conversion, or any non-ability
  source). Allow.
- `abilityId != ''` AND `source.ControllerId != Kaspar.ControllerId` (and not
  uncontrolled) → opponent's ability. Block.

This handles "move wounds to Kaspar" naturally because `Action_02010`'s
move-wounds is implemented as a heal + a wound event with the action card as
source and its ability id set. If an opponent's hypothetical move-wounds
ability targets Kaspar, the wound event's source has the opponent's
ControllerId — blocked.

WHY not check `CHOSEN_PERFORMER`: unlike Maxime ("abilities he performs"),
Kaspar's text is about *who controls* the source, not who performs. The
source card's `ControllerId` is the authoritative answer.

## Technique — modeled on Technique_03004 (Elena Agnelli)

Elena's technique is the closest analog: in-duel, conditional on a property of
the owner's combat situation, "wound the adversary" via
`EventDuelCalculateTechniqueValues` queuing `createCharacterBeingWoundedEvent`.

Differences:
- Elena's condition reads `getCombatCardsForCurrentRound()` and filters by
  the owner's combat card having Sorcery. Kaspar's condition reads
  `$owner->Attachments` *and* `getCardObjectsAtLocation(LOCATION_DUELING_LINE,
  $owner->ControllerId)`, both checking `hasTrait("Eisenfaust")`.

### Why dueling line scope = owner's controller

"In *his* dueling line" — the owner's side of the dueling line.
`getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)` is the
established pattern (Technique_02034, _03004 use the same filter). Gating on
`IN_DUEL` + actor == owner ensures the dueling line we read is the current
duel's, not stale.

### Wound side effect of the Eisenfaust check

`Eisenfaust` is a trait shared by Eisen weapons/armor (`_01047` Kaspar's
Panzerhand, `_01052`, `_01054`, `_02017`, `_02020`). Pulling attachments via
`$owner->Attachments` and lookup-by-id matches `_01054 Maneuver_01054.php`'s
existing usage `if ($attachment && $attachment->hasTrait("Eisenfaust"))`.

## Pre-commit hook compliance

- Not `ISorcererAbility`, not extending any Action base that requires
  `createActionResolvedEvent`, not a `CardReaction`/`AttachmentReaction`/
  `RiskReaction`. No special required-call gates apply.
- Technique handles `setUsed` automatically via `Technique::handleEvent`
  hooking `EventTechniqueActivated`.

## Things I considered and rejected

- **handleEvent on EventCharacterWounded (Maxime pattern)**: simpler-looking
  but bypasses the past-tense event entirely by suppressing `parent::handleEvent`.
  Other cards listening to the past-tense event on Kaspar would still fire
  (e.g. a reaction "when an opposing character is wounded"). Using
  `eventCheck` to zero `wounds` is cleaner — `EventCharacterWounded` is never
  created at all.
- **Checking CHOSEN_PERFORMER**: Kaspar's text isn't about who performs; it's
  about whose ability it is. Source's controller is the right signal.
- **Including IAbilityThatTargetsCharacters on the Technique**: the adversary
  in a duel is auto-determined, not a player-selected target. Technique_03004
  doesn't implement it either; the convention is "duel adversary != targeted
  character."

## What I'd flag for a future audit

- The wound-block's source-controller check assumes every ability-emitted
  wound event sets a sane `ControllerId` on the source card. If a future
  ability emits a wound from a card that's somehow uncontrolled (`ControllerId
  == 0`), we let it through. That seems right ("not an opponent's ability"),
  but worth re-checking if a corner case shows up.
- The Technique fires on every `EventDuelCalculateTechniqueValues` for its own
  id; standard pattern. If the technique is canceled, `parent::handleEvent`
  will have already setUsed(false) on EventTechniqueCanceled? Actually no —
  `Technique::handleEvent` only sets used on Activated, not Canceled. The
  Used flag stays true once activated even if canceled. This matches other
  techniques (03004, 01090). Not a 03014 concern.
