# 07 — Reactions

← [[06 — Actions|FactionAttachment Guide Actions]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[08 — Techniques and Maneuvers|FactionAttachment Guide Techniques And Maneuvers]]

When the printed text has **Reaction** or **City Reaction**, create an `AttachmentReaction` class and register it on the card.

## File and base class

```
modules/php/cards/<expansion>/reactions/Reaction_NNNNN.php
```

```php
class Reaction_NNNNN extends AttachmentReaction
```

`AttachmentReaction` adds `ownerIsAttached(Theah)` so you can bail out when the parent attachment is not equipped.

## Default gate: must be equipped

The event hub dispatches events to **all** tracked cards, including cards in hand. Attachment Reactions should only fire while equipped:

```php
if (! $this->isAvailable()) return;
if (! $this->ownerIsAttached($event->theah)) return;
```

**Exception:** text that equips *from* a special zone (e.g. dueling line) gates on that zone instead — rare.

## Pre-commit literals (required)

Somewhere in the Reaction class body the hook must see all three:

- `$this->setUsed(`
- `$this->isAvailable(`
- `$this->ownerIsAttached(`

## Simple single-decision skeleton

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if (! $this->isAvailable()) return;
    if (! $this->ownerIsAttached($event->theah)) return;

    $owner = $this->getOwningAttachment($event->theah);
    if ($owner == null || $owner->Engaged) return; // if engage is the cost

    if ($event instanceof EventSomething && /* trigger matches */)
    {
        $owner->IsUpdated = true;
        $event->theah->queueEvent(EventFactory::createReactionTransitionEvent(
            $owner->ControllerId, $owner->Id, $this->Id
        ));
    }
}

public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
{
    parent::performReaction($game, $state, $internalId, $reactionId);

    if ($reactionId === 'doEffect')
    {
        $owner = $this->getOwningAttachment($game->theah);

        $game->theah->queueEvent(EventFactory::createCardEngagedEvent(
            $owner->ControllerId, $owner->Id, $owner->Id, $this->Id
        ));

        // … effect …

        $this->setUsed($game->theah, true);
    }

    $game->gamestate->nextState('done');
}
```

Mirror: `Reaction_01022` (simple wound challenger / wound challenged / pass).

`setUsed` resets at dusk automatically. Pass should **not** mark used.

## Engage as a cost

If text says "engage this card":

1. Gate the **offer** on `!$owner->Engaged` (and `isAvailable`).
2. Queue `createCardEngagedEvent` on accept in `performReaction`.

### Multi-stage warning

If a later stage still needs another reaction transition, **do not** `setUsed` on the first Engage click. `runEvents` skips reaction transitions when `!isAvailable()`. Defer `setUsed` to finalize after the last stage (Matushka's Shears `_03007`, Torres Cloak `_03044`).

## Cross-player / multi-stage Reactions

When the **opponent** must choose something (sink cards, accept cancel, …):

- Use a `$stage` field + `$owner->IsUpdated = true`.
- Queue `createReactionTransitionEvent($opponentId, $owner->Id, $this->Id)`.
- Do **not** invent a High Drama GameState for this — Reactions can fire from many phases; a sub-state mapped under one EVENTS table only works in that phase.

Mirror: `Reaction_03007` (Shears), `Reaction_03006` (Scheme sister — same button pattern).

## Cancel Maneuver / Technique

| Do | Don't |
|---|---|
| Listen on `EventTechniqueActivated` / `EventManeuverActivated` | Listen on `EventResolve*` |
| Set transition `priority = Event::HIGH_PRIORITY` | Leave default (Resolve can fire mid-choice) |
| Compare character ids correctly for "adversary" | Copy `Reaction_01047`'s player/character id mix-up |

**Cancel unless discard** (Torres Cloak `_03044`): cancel-**first** on Engage (delete Resolve events, clear `CHOSEN_*`, do not fire `*Canceled` yet). Discard re-queues Resolve; Accept Cancel fires `*Canceled`. Store ids as **public** fields on the reaction.

Hard cancel (single click): `Reaction_01047` / `Reaction_01146b` for the delete + canceled event shape — but prefer `_03044::isAdversaryActivating` for id gates.

## "After an opposing character moves to an adjacent location"

Trigger on **`EventCardMoved`** (after the move), not `EventCardMoving`.

Because the mover has already left, "opposing" is checked with **`fromLocation == owningCharacter->Location`** (same location *before* the move) plus different controller.

Mirror: Kaiser Schnurrbart `Reaction_03019` (attachment), Horatio `Reaction_01066` (character twin).

When the Reaction **moves** someone as an effect, pass `engage=false` on `createCardMovingEvent` and emit engage events separately if needed.

## Sorcerer Reactions

Only if the printed keyword includes **Sorcerer**:

```php
class Reaction_NNNNN extends AttachmentReaction implements ISorcererAbility
```

Emit both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit requires both). Typical: Start when the player accepts; Played in finalize.

**Strega Reaction** / **Diplomat Reaction** / etc. are **not** Sorcerer — only trait-gate the performer.

## Sink vs discard in Reaction effects

| Word | Operation |
|---|---|
| Sink from hand | `insertCardOnExtremePosition` into faction deck |
| Discard from hand | discard-from-hand event |

Match the printed word. When a pool is exhausted mid-"sink two," finalize early ("if able") and **log why** before the fallback effect.

## Checklist for this pattern

- [ ] `extends AttachmentReaction`
- [ ] Literals: `setUsed`, `isAvailable`, `ownerIsAttached`
- [ ] Equipped gate (or documented zone exception)
- [ ] Pass does not `setUsed`; accept does (unless Continuous / deferred finalize)
- [ ] Cross-player uses reaction transitions, not HD GameStates
- [ ] Cancel interrupts use Activated + HIGH_PRIORITY
- [ ] `ISorcererAbility` only for printed Sorcerer

## Next

Duel Techniques and Maneuvers → [[08 — Techniques and Maneuvers|FactionAttachment Guide Techniques And Maneuvers]]
