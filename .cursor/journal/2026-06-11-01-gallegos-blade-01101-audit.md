# Gallegos Blade (_01101) Audit

## Card Text
- Passive (attachment): "When the equipped character gambles during a duel, reveal an additional card."
- Technique: "-1 Parry • During the adversary's next round, they reveal one less card when they gamble."

Castille FactionAttachment. 0 wealth, 0/1/4 R/P/T (Riposte dashed). Traits: Weapon, Melee, Sword, Aldana.

## Component Inventory
- `modules/php/cards/_7s5s/_01101.php` — FactionAttachment, IHasTechniques, TechniqueTrait.
- `modules/php/cards/_7s5s/techniques/Technique_01101.php` — the technique (one entry in `$this->Techniques`).
- Parents: `FactionAttachment` → `Attachment` → `Card`. Technique extends `cards\techniques\Technique`.

## Things That Look Right
- **Passive +1 reveal** in `_01101::getNumberOfGambleCardsToReveal` gates on `isAttached() && $actor->Id == $attachedTo->Id`. Gamble only runs during a duel (called from `actDuelActionGamble` and `Maneuver_01114::handleEvent` on `EventResolveManeuver`), so the "during a duel" qualifier is implicitly satisfied without an `IN_DUEL` check.
- **-1 Parry** is wired via `EventDuelCalculateTechniqueValues` with `$event->parry -= 1` — same shape as `Technique_PlusOneParry`. ✓
- **IsActivated lifecycle** is sound:
  - Flips true on `EventResolveTechnique` matching this technique Id.
  - Resets on `EventTechniqueCanceled`, `EventDuelEnd`, and on `EventDuelEndOfRound` when the actor of that round is not the owning character.
  - Because the duel actor alternates each round, the first end-of-round with `actor != owner` is exactly the end of the adversary's "next round". ✓
- **IsUpdated propagation**: every flag change sets `$owner->IsUpdated = true`, so the technique's serialized state (it's nested inside the attachment's Techniques array) persists across queue→dispatch cycles.
- **`isAvailableToPlayer`** correctly gates on the `Game::IN_DUEL` global (matches `Technique_PlusOneParry`). ✓

## Bug Found

### `Technique_01101::getNumberOfGambleCardsToReveal` doesn't restrict to the adversary

Old code:
```php
if ($this->IsActivated)
{
    $owner = $this->getOwningCard($theah);
    $count -= 1;
    $explanations[] = sprintf($theah->game->translate("%s: -1."), $owner->getInjectCode());
}
```

This applies -1 to **any** actor's gamble while `IsActivated` is true. Card text restricts the penalty to the adversary.

**Concrete failure mode:** in the activator's own round, the player can pick `actDuelActionChooseTechnique` → resolves Technique_01101 → state machine transitions through `DUEL_CHOOSE_TECHNIQUE_EVENTS` and returns to `DUEL_CHOOSE_ACTION` (see `states.inc.php:2170`). The same actor can then pick `actDuelActionGamble` in the same round. With `IsActivated == true` and `actor == owning character`, the technique was subtracting 1 from the activator's own reveal count, exactly cancelling the attachment's passive +1. So a gamble that should have been "+1 because you're holding Gallegos Blade" was netting 0.

The intended target — the adversary's next round — was working correctly: at end of activator's round, `actor == owner`, so no deactivation; in the adversary's round, the -1 applies; at end of adversary's round, `actor != owner` triggers deactivation.

**Fix applied:** added a check that `$actor->Id != $owningCharacter->Id` (with a `null` guard on the owning character) before applying the -1, plus a `// WHY` comment explaining the same-round gamble path so a future agent doesn't simplify the gate away. Improved the explanation string to mention "(adversary's next round)" so the duel log explains the source.

Compared to `Reaction_01100` (which tracks an explicit `$AdversaryId` set at activation): that pattern would also work, but it's heavier here. During a duel there are exactly two duelists, one of which is the equipped character, so "not me" is unambiguously "the adversary" — no need to snapshot the adversary id separately. If multi-duel ever becomes a thing, this assumption would break; not a concern today.

## WHY Notes for Future-Me
- Don't fold the actor check back into "`if ($this->IsActivated)`" — it's load-bearing. The activator can still gamble in the same round after resolving the technique because `techniqueChosen → DUEL_CHOOSE_TECHNIQUE_EVENTS → endOfEvents → DUEL_CHOOSE_ACTION` returns control to the same actor.
- The end-of-round deactivation (`actor != owner` → false-out) **looks** redundant with `EventDuelEnd` but is the only thing that scopes "their next round" correctly: if the adversary keeps dueling for additional rounds after their "next" round, the -1 must not persist into those subsequent rounds.
- `getOwningCharacter` returns the attached-to character for techniques living on attachments; for a character-owned technique it would return the character directly. Both work here because the only place this technique lives is on Gallegos Blade.

## Follow-up: Unequip Mid-Duel

User flagged a second hole: if Gallegos Blade is unequipped during a duel (e.g., the equipped character is destroyed, or an effect strips the attachment) while `IsActivated == true`, the technique state should clear.

Without a handler, `IsActivated` would persist on the (now-detached) attachment object. If the same attachment somehow gets re-equipped later — even in a different duel — its `-1 reveal` could leak into a future round before the next `EventDuelEnd` reset. The persistence is real because `IsUpdated` causes the technique state to be serialized back to DB.

Added an `EventAttachmentUnequipped` handler that matches on `$event->attachmentId == $this->OwnerId` (since the technique's owner is the attachment) and clears `IsActivated`, with the standard `$owner->IsUpdated = true` to flush the change. Imported `EventAttachmentUnequipped`.

### WHY for future-me
- `EventDuelEnd` is not enough on its own: an unequip mid-duel doesn't necessarily end the duel (e.g., a separate character is wounded mid-duel triggering an unequip via some other effect). The flag has to clear when this specific attachment leaves play, independent of the duel end.
- Using `OwnerId` rather than `getOwningCard()` keeps this cheap and safe even if the card lookup is in an odd state at unequip time.

## Not Changed
- The passive +1 explanation string ("+1 for being attached to acting character.") in `_01101::getNumberOfGambleCardsToReveal` is slightly unusual phrasing but not wrong. Left as-is.
- No explicit `IN_DUEL` check on the passive — it's load-bearing-irrelevant because gamble-reveal-count is only computed inside a duel. If a non-duel "gamble" path is ever added, revisit.
