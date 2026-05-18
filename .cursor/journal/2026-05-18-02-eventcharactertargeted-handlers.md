# Add EventCharacterTargeted Handlers to Targeting-Gated Reactions

## Context

A new event class `EventCharacterTargeted` was added (uncommitted file
`modules/php/theah/events/EventCharacterTargeted.php`). Comment: "Sent
when a character is targeted by an ability. So far only used by Defending
Honor."

Carries `playerId, targetId, sourceId, abilityId`.

User asked: find every card/ability whose `handleEvent` already reacts
to events fired by `IAbilityThatTargetsCharacters` abilities, and add a
parallel branch for the new `EventCharacterTargeted`.

## Audit of candidates

Searched for `instanceof IAbilityThatTargetsCharacters` inside
handleEvent (or helpers called from it). **NOTE:** Also need to search
for `instanceof IRiskThatTargetsCharacters` separately — these are
distinct marker interfaces (the Risk one does NOT extend the Ability
one). Missed Maryam (`_01186.php`) on first pass for exactly this
reason.

**Updated:**
- `cards/_7s5s/reactions/Reaction_01014.php` — Vittoria "swap to a
  Thug instead". `shouldReactToEvent()` gates on
  `IAbilityThatTargetsCharacters`. Branches already exist for
  `EventCardEngaged`, `EventCardEngarded`, `EventCardMoving`,
  `EventCharacterBeingWounded`, `EventCharacterBeingHealed`,
  `EventChallengeIssued`.
- `cards/_7s5s/reactions/Reaction_01032.php` — Red Hand risk cancel.
  `shouldReactToEvent()` gates on
  `IAbilityThatTargetsCharacters || IAbilityThatTargetsCards`. Same
  set of branches as Vittoria plus `EventRiskReactionTriggered`.
- `cards/tac/reactions/Reaction_02016.php` — Diplomatic Impunity
  attachment redirect. `shouldReactToEvent()` gates on
  `IAbilityThatTargetsCharacters`. Same set of branches.
- `cards/_7s5s/_01186.php` — Maryam Benu Pleroma "Forced: cancel the
  first Risk that targets her each Day". **Added on the user's prompt
  after the initial pass missed it.** Her gating is `$source instanceof
  Risk && $source instanceof IRiskThatTargetsCharacters`, not the
  ability interface — so an interface-only grep didn't catch her.
  Defending Honor's source card `_01078` extends `Risk` and implements
  `IRiskThatTargetsCharacters`, so EventCharacterTargeted from it
  satisfies her trigger. The new branch mirrors her existing pattern
  (mark ability used, deleteEventBatch, cancel event).
- `cards/tac/reactions/Reaction_02048.php` — "Pressure with Combat to
  Cancel Risk Effects". Same gating as Maryam (`Risk` +
  `IRiskThatTargetsCharacters`). Added: new `$characterTargetedEvent`
  field, new branch using `$event->targetId`, plus added
  `EventCharacterTargeted` to (a) the post-pressure auto-cancel block,
  (b) the `skipNextEvent` block, (c) `clearSavedEvents`, and (d)
  `reEmitSavedEvent`.
- `cards/_7s5s/reactions/Reaction_01053.php` — Hexenjagd "When a
  Sorcerer ability targets a card • Wound your performer at that
  location and cancel the effects." Existing handler only gates on
  `IAbilityThatTargetsCards` against `EventSorcererAbilityStart`,
  which silently misses Sorcerer abilities that target characters
  (the two interfaces are mutually exclusive per CLAUDE.md). Added
  a parallel `EventCharacterTargeted` branch gated on
  `ISorcererAbility` (since EventCharacterTargeted fires for any
  IAbilityThatTargetsCharacters ability, not just sorceries). Same
  Location-not-Home + has-performers-at-location gating as the
  existing branch.
- `cards/faf/_03cd21.php` — Silver Spine (City Attachment).
  **Missed in the initial IRiskThatTargetsCharacters audit and only
  caught when the user asked.** Same shape as Maryam: cancel the
  first opponent's Risk targeting the equipped character each day.
  Uses helper `isOpponentRiskTargetingCharacters()` which contains
  the interface check (line 135) — my first grep apparently didn't
  surface this match; re-running it found Silver Spine plus a
  template hit in `.claude/skills/create-city-attachment/SKILL.md`.
  Added a parallel `EventCharacterTargeted` branch keyed on
  `$event->targetId == $this->AttachedToId`, mirroring the existing
  `EventCharacterBeingWounded` branch shape.
- `.claude/skills/create-city-attachment/SKILL.md` — updated the
  "events to repeat" comment in the skeleton to include
  `EventCharacterTargeted (targetId)`. Without this, future cards
  minted from the template would have the same gap that Silver Spine
  did. (Doc-only change, no game behavior.)
- `cards/_7s5s/reactions/Reaction_01122.php` — Torsten Vakt "Cancel a
  Sorcery or Sorcerer Ability Targeting Torsten Vakt." Added an
  `EventCharacterTargeted` branch gated on `ISorcererAbility` and
  `owner->Id == event->targetId`, mirroring his existing
  `EventSorcererAbilityStart` branch (captures `sourceId` + `batchId`,
  stacks a reaction transition). Same potential double-trigger risk
  flagged in the Open Questions section — `isAvailable()` plus
  `deleteEventsTargetingCard`/`deleteEventBatch` on cancel should
  absorb it in practice, but worth verifying once the firing side is
  built.
- `cards/_7s5s/reactions/Reaction_01008.php` — Cesca "Copy Sorcerer
  Ability Just Played". Added on user prompt: "It is possible in the
  future that sorcery abilities will emit this new event." Wired up a
  new `EventCharacterTargeted` branch that mirrors the existing
  `EventSorcererAbilityPlayed` detection but with two key constraints:
  (1) gates on `$ability instanceof ISorcererAbility` since
  EventCharacterTargeted is fired by any IAbilityThatTargetsCharacters
  ability, not just sorceries; (2) cannot detect the
  `performer == cesca` path because EventCharacterTargeted has no
  `performerId` field — that path stays on the EventSorcererAbilityPlayed
  handler.

## Verified audit: IRiskThatTargetsCharacters reactors

Re-audited after the user surfaced Silver Spine. The correct full
list of files that USE the interface as a gate (versus just
implementing it):

- `cards/_7s5s/_01186.php` (Maryam) — wired
- `cards/tac/reactions/Reaction_02048.php` — wired
- `cards/faf/_03cd21.php` (Silver Spine) — wired (added after user
  pointed it out — my first audit missed it)

The other 27 matches are Risk cards that *implement* the interface
(declare themselves as Risks that target characters). They don't react
to events from this interface, so they're not in scope.

**Audit-process note for future agents:** Don't trust a single grep
pass for `instanceof IRiskThatTargetsCharacters`. Cards that wrap the
check in a helper (e.g., Silver Spine's `isOpponentRiskTargetingCharacters`)
were apparently missed by my first scoped grep, even though the text
was present. Re-run unscoped and verify by counting matches against
the implementer list.

**Considered and skipped (with reasoning):**
- `Reaction_01008.php` (Cesca) — initially skipped on the reasoning
  that she copies only Sorcerer abilities and EventCharacterTargeted
  is only fired by Defending Honor right now. User pushed back: "It
  is possible in the future that sorcery abilities will emit this new
  event." Now wired up — see Updated list above.
- `Reaction_01122.php` (Torsten Vakt) — initially skipped because his
  card text says "Sorcery or Sorcerer Ability" and his current handler
  relies on `EventSorcererAbilityStart` (which implicitly filters to
  sorceries). User pushed back. Future-proofed by adding an
  `EventCharacterTargeted` branch gated on `ISorcererAbility` so he
  still cancels sorcery-character-targeting if/when that info migrates
  off `EventSorcererAbilityStart`. Now wired up.
- `Reaction_01053.php` (Hexenjagd) — initially skipped because it
  only gates on `IAbilityThatTargetsCards`. User pushed back asking
  if it needs rewiring. Re-examining: the card text reads "Sorcerer
  ability targets a **card**" and characters are cards. Per CLAUDE.md
  `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` are
  mutually exclusive, so the existing handler silently misses
  Sorcerer abilities that target characters. Added an
  `EventCharacterTargeted` branch gated on `ISorcererAbility` to
  close that gap. Now wired up — see Updated list.
- `Reaction_02001.php` (Adriana) — implements
  `IAbilityThatTargetsCharacters` herself but her `handleEvent`
  responds to `EventCharacterIntervened` and `EventChallengeRejected`
  without gating on the source's interface. Not in scope.
- `BasicChallengeAction`, all the `Action_*` files that implement
  `IAbilityThatTargetsCharacters` — they implement the interface, but
  their `handleEvent` methods do NOT gate on it; they handle their own
  action lifecycle.

## What the new branch does

For each of the three updated reactions, I mirrored the existing
`EventCharacterBeingWounded`-style branch:

1. Add `use EventCharacterTargeted`.
2. Add `private ?EventCharacterTargeted $characterTargetedEvent = null;`.
3. New `if ($event instanceof EventCharacterTargeted ...)` branch in
   `handleEvent` that:
   - Resolves the owning card.
   - Checks `targetId` matches owner (in Vittoria's and DI's cases) or
     that owner's controller matches the target's controller (Red Hand
     in-hand case).
   - Confirms `$event->playerId` is not the owner's controller (or
     equivalent — Red Hand uses target ownership instead).
   - Calls `shouldReactToEvent()` (which performs the
     `IAbilityThatTargetsCharacters` check) so behaviour is consistent.
   - Clones the event, marks `canceled`, stashes for later release,
     queues the reaction transition.
4. In the per-reaction `releaseEvent()`, set
   `characterTargetedEvent->targetId = $characterId` then re-queue.
5. Add `$this->characterTargetedEvent = null;` in
   `cancelEvents` / `clearEvents`.

## WHY: design decisions

**Mirror existing branches verbatim instead of refactoring.** All three
reactions have a lot of duplication across their `EventCard*` /
`EventCharacter*` / `EventChallengeIssued` branches. Tempting to
deduplicate but: (a) the gating differs subtly per event (e.g., Red Hand
checks the wound-target's controller via `event->characterId`, but
EventChallengeIssued has both challenger and defender), and (b) the user
prefers minimal, targeted edits. The new branch is a straight copy of
the closest existing analogue.

**Match the existing `cancelEvents()` quirks.** Reaction_01014 and
Reaction_02016's `cancelEvents()` unconditionally set
`CHALLENGE_CANCELLED = true` even when no challenge event was stashed
— this is pre-existing behaviour, not something I introduced. Left it.

**Did not add `EventFactory::createEventCharacterTargeted()` or wire it
up in `Events.php`.** The user has a commented-out call in
`Action_01078.php:95-96`:

```php
// $eventCharacterTargeted = EventFactory::createEventCharacterTargeted($performer->ControllerId, $performer->Id, $this->Id, $this->Id);
// $event->theah->queueEvent($eventCharacterTargeted);
```

So they're staging the firing side themselves. Task scope was strictly
"update handlers" — leaving the factory + registration for the user.

**Did not register `EventCharacterTargeted` in `theah/Events.php`
either.** Same reasoning. Without registration, `createEvent()` can't
instantiate it, so the user will need to add the constant when they
hook up the firing side. Not adding it pre-emptively keeps the diff
contained to the requested change.

## Open question / future risk

When `EventCharacterTargeted` does start firing (Defending Honor or
later), the existing downstream events (`EventChallengeIssued`,
`EventCharacterBeingWounded`, etc.) will still fire too — so the same
reaction could trip twice on a single ability (once at targeting, once
at the side-effect). The `skipNextEvent` guard inside each reaction
should absorb the second trip in the common case, but cross-reaction
interactions (e.g., Vittoria's swap fires on targeting, then Red Hand
also tries to swap on the resulting wound event) might need attention
when the firing side is implemented. Flagging for the next session.
