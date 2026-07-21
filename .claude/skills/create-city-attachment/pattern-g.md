> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern G — Forced Once-Per-Day Cancel of Opponent Risks

Card text shape: "**Forced:** Each Day, the first time an opponent's Risk targets the equipped character • Cancel the effects." Canonical: `_03cd21` (Silver Spine), modeled on `_01186` (Maryam — same shape but on a `CityCharacter`).

A Risk that targets a character can fire one of five event types. You must intercept all five:

| Event | Target field | Notes |
|---|---|---|
| `EventCardMoved` | `cardId` | Risk moves the character. |
| `EventCardEngaged` | `cardId` | Risk engages the character. |
| `EventChallengeIssued` | `defenderId` | Risk issues a challenge against the character. |
| `EventCharacterBeingWounded` | `characterId` | Risk wounds the character. |
| `EventAttachmentEquipping` | `characterId` | Risk equips a (hostile) attachment onto the character. **Requires a manual discard branch — see below.** |

### Skeleton (adapt from `_03cd21`)

```php
public function handleEvent(Event $event)
{
    if ($this->isAttached() && ! $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED) &&
        (($event instanceof EventCardMoved && $event->cardId == $this->AttachedToId && $event->sourceId != 0) ||
        ($event instanceof EventCardEngaged && $event->cardId == $this->AttachedToId && $event->sourceId != 0))
    ) {
        if ($this->isOpponentRiskTargetingCharacters($event, $event->sourceId)) {
            $this->markAbilityUsed($event->theah->game);
            $event->canceled = true;
            return;
        }
    }
    // ... repeat for EventChallengeIssued (defenderId), EventCharacterBeingWounded (characterId),
    //     EventCharacterTargeted (targetId), and EventAttachmentEquipping (characterId — with
    //     manual discard, see below).

    parent::handleEvent($event);

    if ($event instanceof EventDuskEndOfDay && $this->hasCondition(Game::SILVER_SPINE_ABILITY_USED)) {
        $this->removeCondition(Game::SILVER_SPINE_ABILITY_USED);
        $event->theah->game->notify->all("silverSpineAbilityRemoved", "", ["cardId" => $this->Id]);
    }
}

private function isOpponentRiskTargetingCharacters(Event $event, int $sourceId): bool
{
    $source = $event->theah->getCardById($sourceId);
    return $source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters
        && $source->ControllerId != $this->ControllerId;
}
```

### Critical adapters when porting Maryam's character-side pattern to an attachment

1. **Target field = `$this->AttachedToId`**, not `$this->Id`. The trigger fires when the *equipped character* is the target, not the attachment itself.
2. **`isAttached()` guard at the top of every branch.** While the attachment is in the city deck / discard / itself being equipped, it has no protected character — the trigger must no-op. Without this guard, `$this->AttachedToId == 0` matches "card id 0" coincidentally and you get spurious cancels.
3. **`$source->ControllerId != $this->ControllerId`** for "opponent's." On a city attachment, `ControllerId` mirrors the equipped character's controller, so this naturally reads as "Risk played by someone other than the player who controls the equipped character." Skip this check only if the card says "any Risk" (Maryam) instead of "opponent's Risk."
4. **`Conditions` live on the attachment, not on the character.** The once-per-Day is a property of *the artifact*. If it's unequipped and re-equipped mid-day, the spent state travels with the card — which is the rules-correct read.

### `EventAttachmentEquipping` needs a manual discard branch

When you cancel `EventAttachmentEquipping`, the matching `EventAttachmentEquipped` never fires — so the would-be attachment is left in limbo (DB says it's at the character's location, but in-memory state never finalized). Discard it by hand inside the same branch:

```php
$attachment = $event->theah->getCardById($event->attachmentId);
if ($attachment) {
    $removedEvent = EventFactory::createCardRemovedFromPlayEvent($event->playerId, $attachment->Id, $attachment->Location);
    $event->theah->queueEvent($removedEvent);

    if ($attachment instanceof CityAttachment) {
        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($event->playerId, $attachment->Id, $attachment->Location);
        $event->queueEvent($discardEvent);
    } else {
        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location);
        $event->queueEvent($discardEvent);
    }
}
```

`Reaction_02048` (Blood Like Winter) and `_01186` use the identical CityAttachment-vs-faction split — keep them in sync if you change it. The 2026-04-14-04 RiskClone journal documents a related bug where `createCardInLocation` cards didn't land in `Theah::$cards` and the discard branch silently desynced; that fix is now in `Action_02008`, but be aware if you mint a new "Risk that equips a clone" mechanic.

### `sourceId` nullability across the five events

`EventAttachmentEquipping::sourceId` is `?int`; the other four are `int`. `$event->sourceId != 0` works for both — in PHP loose comparison, `null != 0` is `false`, so null-source events filter out (no Risk = nothing to cancel). Keep this idiom; the strict variant adds a `!== null && !== 0` for no behavior change.

### Five-file UI plumbing for the once-per-Day condition + chip

Maryam, Carmella, and Silver Spine all share this dance. Pick a `<CARD>_ABILITY_USED` condition name and replicate it in:

1. **`modules/php/Game.php`** — `final const <CARD>_ABILITY_USED = "<Display Name>";` near the other condition consts.
2. **`seventhseacityoffivesails.js`** — `this.<CARD>_ABILITY_USED = '<Display Name>';` near the other condition aliases.
3. **`modules/js/Notifications.js`** — add two entries to the notif-list (`['<card>AbilityUsed', 500], ['<card>AbilityRemoved', 500]`) and two handler functions (`notif_<card>AbilityUsed` / `notif_<card>AbilityRemoved`).
4. **`modules/js/Utilities.js`** — add a chip-render block inside `createAttachmentCard` (or `createCharacterCard` for character-side patterns). **There is no generic conditions loop for attachments — you must add the `if (attachment.conditions?.includes(this.<CARD>_ABILITY_USED)) { ... }` block by hand.** Without it, the chip is missing on page refresh after the ability has been used.
5. **`seventhseacityoffivesails.css`** — `._7sfs-<card>-ability-used-chip` class with a 25x25 background-cropped chip image and an anchor position.

### Chip-removal id mismatch — DO NOT copy verbatim from Maryam/Carmella

Maryam and Carmella both have a latent bug where the chip is *placed* with `` `${card.divId}_<card>_ability_used` `` (full DOM id, e.g. `${controllerId}-${cardId}`) but *removed* with `` `${args.cardId}_<card>_ability_used` `` (bare card id). `dojo.destroy` silently no-ops when the id doesn't exist. They get away with it because the dusk-end-of-day flow often redraws the play area; new patterns will not. **Use `card.divId` in both placement AND removal:**

```js
// placement (notif_*AbilityUsed)
const id = `${card.divId}_<card>_ability_used`;
// removal (notif_*AbilityRemoved) — MUST also use card.divId
const id = `${card.divId}_<card>_ability_used`;
dojo.destroy(id);
```

### Chip CSS position when chip lives on an attachment

Attachments are splayed under the equipped character via `._7sfs-attached-card { left: calc(var(--attachment-index) * -15px); }`. Only the leftmost ~15px strip of each un-splayed attachment is visible. Anchor your chip at `left: 0; top: 0; z-index: 20;` so it stays visible whether splayed or not. Copying Carmella's `left: 80px; top: 30px;` (designed for a full character card) will hide the chip entirely under the next attachment in the splay.

### `getInjectCode()` for notify messages

When announcing "this card cancels X," use `${card_inject_code}` in `clienttranslate` and pass `"card_inject_code" => $this->getInjectCode()` in the notify args. Gives the player a clickable card-name reference in the log. `Card::getInjectCode()` is defined on the base — works on every card type.
