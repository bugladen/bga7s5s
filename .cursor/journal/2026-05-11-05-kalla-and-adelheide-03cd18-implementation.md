# Kalla and Adelheide (`_03cd18`) — implementation

City Character with Negotiable + a branching post-recruit reaction. Wired
`IHasReactions` + `ReactionTrait` on the card class and created
`Reaction_03cd18` as a button-based multi-stage reaction.

## Stage map

```
'' (idle)
 └─ choose          two options + Decline (filtered by validity)
      ├─ optionA → searchA  (pick attachment from deck)
      └─ optionB → moveB    (pick destination location)
                    └─ destroyB (pick opposing attachment to destroy)
```

`< Back` is offered in `searchA` and `moveB` (both rewind to `choose`). It is
deliberately **not** offered in `destroyB` because by the time we reach that
stage the move event has already committed — rewinding would also need to
un-move Kalla, which isn't a thing the engine supports. If the player wants to
bail at `destroyB`, they can Decline.

## Trigger

Only `EventCharacterRecruited` with `characterId == owner.Id`. Unlike Julius
(`_03cd10`), this card's text doesn't fire on movement — only on recruitment.

Two guards beyond identity:
- `cardInCity($owner)` — recruitment doesn't change Location, so reading
  `$owner->Location` is safe here (no `EventCardMoved` ordering gotcha).
- `hasAnyValidOption(...)` — if neither search nor move/destroy has any valid
  target, we don't trigger at all. Avoids presenting an empty "Decline only"
  reaction.

## Valid-option gates

- **Option A**: at least one `Attachment` in the player's faction deck.
- **Option B**: at least one city location where an opposing-controller
  character has at least one equipped attachment. "Opposing" = different
  controller AND same location (per project memory) — so I filter per-location,
  not globally.

Both options are gated independently inside `getReactionButtonProperties` so
the choose stage shows only the viable ones. If a player declines, both are
unavailable, etc.

## Why "destination must already have an opposing attachment"

The text reads "move them to any location **and** destroy target attachment
equipped to an opposing character." The conjunction means Option B does both
in one resolution. After the move, "opposing" = different controller at
Kalla's new location. So a destination is only legal if, post-move, there will
be an opposing attachment there.

Edge case: I re-verify in `resolveDestroy` (controller + location match) in
case a concurrent effect moved either the attachment or Kalla between the
move event committing and the player clicking. If the re-check fails, the
destroy is silently a no-op rather than throwing — the reaction is already
consumed via `setUsed(true)`, no need to error the player out.

## Event ordering in Option B

```
performReaction('moveB-<location>'):
    queueEvent(CardMovingEvent)        // applies first
    queueEvent(ReactionTransitionEvent) // re-enters playerReaction
```

By the time `getReactionButtonProperties('destroyB')` renders, Kalla's
`Location` is the new city location and `getOpposingCharactersAtLocation`
returns the right list. I read `$owner->Location` (not `$this->chosenLocation`)
in `destroyB` so that if the move was canceled/intercepted, we still operate
against actual current state.

`$engage = false` on the move event, matching the convention used by every
other card-effect move I checked (Penya, Maneuver_01033, Action_01020,
Action_01028, etc.) — card-effect moves don't engage the character.

## Why button-based and not a full state class

Per the skill: button-based is the right shape until the UI needs richer
selection (board highlighting, multi-select, etc.). Here every choice is a
pick-one from a small enumeration:
- 2 options at `choose`
- N attachments in your deck (typically <15 in a faction deck)
- up to 5 locations at `moveB`
- a small number of attachments at `destroyB`

Buttons are sufficient and avoid creating new States/JS wiring. If the
attachment list ever gets unwieldy this could be promoted to a card picker,
but YAGNI.

## Search resolution

Same pattern as Action_02045 (Path to Poluchatel):
1. Validate the picked card is still in the right deck and still an Attachment
2. `createCardRemovedFromPlayerFactionDeckEvent` → `createCardAddedToHandEvent`
3. Shuffle the player's faction deck via `getGameDeckObject()->shuffle($deckName)`
4. Notify

The shuffle is required per the parenthetical "(Shuffle your deck after
searching.)" and applies only to Option A.

## Destroy resolution

Standard "destroy attachment in play" pattern from Action_01174:
1. `createAttachmentUnequippedEvent` (unequip from character)
2. `createCardDiscardedFromPlayEvent` (discard from play, `asEffect=true`)

This is for normal (non-city) attachments — the typical case for "equipped to
an opposing character." If a city attachment ever ends up here, the discard
event still does the right thing (the engine routes city-vs-normal discards
itself based on the card type).

## Negotiable

Just a constructor flag — `$this->Negotiable = true;`. Mirrored to the
client via the base `CityCharacter::getPropertyArray()` automatically.

## Files touched

- `modules/php/cards/faf/_03cd18.php` — add `IHasReactions` + `ReactionTrait`,
  `Negotiable = true`, wire `$this->Reactions = [new Reaction_03cd18()]`.
- `modules/php/cards/faf/reactions/Reaction_03cd18.php` — new file.

No new state classes, no `states.inc.php` edits, no JS wiring (button-based
reaction).

Pre-commit hook passes (`$this->setUsed(` and `$this->isAvailable(` both
present in the reaction file).
