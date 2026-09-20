# 06 — Reactions

← [[05 — Actions|Scheme Guide Actions]] · [[Index|Implementing a Scheme Card]] · Next: [[07 — When Revealed, Forced, and Passives|Scheme Guide When Revealed Forced And Passives]]

A **Reaction** / **City Reaction** prompts the player after something happens. On Schemes, the shape matches Character Reactions (`CardReaction`) — with one important lifecycle note.

File: `modules/php/cards/<expansion>/reactions/Reaction_NNNNN.php`  
Extends: `CardReaction`

## Wire it on the card class

```php
class _NNNNN extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        // ...
        $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

## Lifecycle: scheme Reactions fire during High Drama

Chosen schemes sit at **`LOCATION_PLAYER_HOME` all day**. `Theah::buildCity()` includes Home cards, so Reactions still receive events during claims, challenges, and pressures.

Do **not** add guards that assume "the scheme left play after resolve."

`Used` resets on `EventDuskEndOfDay` — once per day, same as Character Reactions.

## Basic flow

1. In `handleEvent`, gate `$this->isAvailable()`.
2. Confirm identity (owner controller, traits, location, …) using the **event's** fields.
3. Capture any context you will need later onto `private` fields on the Reaction.
4. Persist with `$owner->IsUpdated = true`.
5. Queue `createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id)`.
6. In `performReaction`, apply effects; `setUsed(true)` on success; Pass does **not** `setUsed`.

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventChallengeRejected && $this->isAvailable())
    {
        $owner = $this->getOwningCard($event->theah);
        if ($owner == null) {
            return;
        }

        $challenger = $event->theah->getCharacterById($event->challengerId);
        if ($challenger == null) {
            return;
        }
        if ($challenger->ControllerId != $owner->ControllerId) {
            return;
        }
        if (! $challenger->hasTrait("Red Hand")) {
            return;
        }

        $this->location = $challenger->Location;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent(
            $owner->ControllerId,
            $owner->Id,
            $this->Id
        );
        $event->theah->queueEvent($transition);
    }
}
```

Reference: `Reaction_03005`. City Reaction on pressure: `Reaction_02004`.

## Capture context at trigger time

By the time the player clicks, the board may have changed.

- Destroyed characters: capture `$location`, trait snapshots, names in `handleEvent`. On `EventCharacterDestroyed`, `$destroyed->Location` is still the destroy-time city slot inside your handler (`runEventHubAfterCards`).
- Surface useful context in `getReactionDescription` so the player knows why they are being prompted.

Reference: `Reaction_03017` (Noble Sacrifice).

## Button Reactions usually need no GameState / no JS

Render choices with `getReactionButtonProperties` (Claim / Pass, location buttons, card buttons). Pure button flows skip [[09 — Wiring|Scheme Guide Wiring States And JavaScript]].

### After you claim • Move a Renown…

City Reaction on `EventLocationClaimed`:

1. Gate controller + claimed location has Renown + another destination exists.
2. Capture claimed location; queue reaction transition.
3. Buttons: one per other city location + Pass.
4. On confirm: batch move Renown events; `setUsed` only on success.
5. Pass clears captured location without `setUsed`.

Reference: `Reaction_03041`.

## Multi-stage Reactions (still buttons, no sub-state)

When several clicks are needed (or the active player changes mid-flow), use a `private string $stage` field and re-queue `createReactionTransitionEvent` for the next player.

Do **not** invent a dedicated GameState for cross-player Reaction steps — Reactions can fire from many phases; a HD-only state would be unreachable.

Reference: `Reaction_03006` (Premonition — Strega trait gate, **not** Sorcerer).

## Bundled effects ("• A. B. C." with no internal "may")

One **Resolve** + **Pass** pair. Once confirmed, all sub-effects fire. Query "characters at that location" at **resolve** time; use **captured** snapshots for traits of destroyed cards.

Reference: `Reaction_03017`.

## Pre-commit

`CardReaction` subclasses must include the literal strings `$this->setUsed(` and `$this->isAvailable(` somewhere in the file.

## Sorcerer vs "after a Sorcerer ability"

| Printed text | What to do |
|---|---|
| **Sorcerer Reaction** / **Sorcerer City Reaction** | `implements ISorcererAbility` + emit start **and** played events |
| Ordinary Reaction after someone plays a Sorcerer ability | Listen on the event; do **not** implement `ISorcererAbility` |

## Next

When Revealed / Forced / Passives → [[07 — When Revealed, Forced, and Passives|Scheme Guide When Revealed Forced And Passives]]  
Or jump to [[08 — Challenge Actions|Scheme Guide Challenge Actions]] / [[09 — Wiring|Scheme Guide Wiring States And JavaScript]]
