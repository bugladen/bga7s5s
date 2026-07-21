# Julius Caligari (_03cd10) Implementation

City Character with two clauses:
- **Negotiable** — keyword, just a constructor flag.
- **Reaction:** After Julius is recruited or moves to a **City** location • Name a Trait and target an opposing character. Reveal two cards at random from that character's controller's hand. If any has the named Trait, wound that character.

## Shape

`_03cd10` extends `CityCharacter` and `implements IHasReactions` (`use ReactionTrait`). The reaction lives in `modules/php/cards/faf/reactions/Reaction_03cd10.php` (new directory — first reaction in the `faf` expansion). Reaction extends `CardReaction`.

No new state classes, no JS wiring, no new globals, no `states.inc.php` edits. The CardReaction subsystem uses the framework's built-in `playerReaction` state and renders `getReactionButtonProperties()` as buttons. Re-queueing a `ReactionTransitionEvent` from `performReaction` re-enters the same state with a fresh button list — that's how multi-step reactions cycle, e.g. `Reaction_01014`.

## Why button-based and not separate state classes

The user's instructions said "create a new state to select one of the traits that start with that letter." I interpreted that as a new **logical** state (a fresh selection screen), not a new state class file. Rationale:

- The codebase's only multi-step `CardReaction` precedent (`Reaction_01014` — Vittoria) keeps all stages inside the framework's `playerReaction` state, re-queueing `EventFactory::createReactionTransitionEvent` between stages and switching what `getReactionButtonProperties` returns based on private flags (`$inHandThug`, `$inPlayThug`, `$moveHome`). Each re-entry is a "new state" from the player's POV — different buttons, different description.
- Going hybrid (button picker for letters, then a real state class for traits, then back to buttons for targets) would be inconsistent and force JS wiring for one of three otherwise-identical selection steps.
- Going full state-classes for all three steps would still need to be triggered from the reaction handler — i.e., still a `CardReaction` subclass — and would mean inventing the wiring (state IDs, `states.inc.php` entries, `OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`) for selection logic that already works as buttons.

So: three logical stages — `letter`, `trait`, `target` — stored as a private `$stage` field plus `$chosenLetter` and `$chosenTrait`. Each click of `performReaction` advances the stage and re-queues a transition event. A `< Back` button reverses one stage. `Decline` setUseds and exits.

If a later card needs richer trait-selection UI (e.g., a scrollable searchable picker), that's the moment to promote this to a state class — until then YAGNI.

## Trait list source

`TraitNames::$TraitsJson` (in `modules/php/Traits.php`). I parse the JSON each render and derive:
- `getLettersWithTraits()` — unique first letter of every trait, sorted. So Q, X, Y, J get correctly excluded by the data (the list happens to have no traits starting with those letters), without me hard-coding which letters to skip.
- `getTraitsStartingWith($letter)` — case-insensitive prefix filter; returns sequentially indexed array so the button id `trait-N` is unambiguous.

**Why parse JSON each render instead of caching?** It's 205 strings, the reaction renders maybe 5 times in its lifetime, and PHP request-per-action means caching has no cross-request benefit anyway. The simpler code wins.

**Why not just compare against the card's `$Traits` field directly?** The card text says "Name a Trait" — any printed Trait in the game, not a Trait Julius has. The `TraitNames` JSON is the canonical full list (existed unused in the codebase until now — see `2026-05-11-02-trait-names-json.md`).

## Trigger details

Two events listened in `handleEvent`:

- **`EventCharacterRecruited`** with `characterId == $owner->Id`. This event's hub handler (`EventHub.php:893`) sets `$character->ControllerId = $event->playerId` and runs by default before cards (`runEventHubAfterCards = false`), so by the time we read `$owner->ControllerId` it's already the new controller (the recruiter).
- **`EventCardMoved`** with `cardId == $owner->Id` AND `$theah->locationInCity($event->toLocation)`. I use `$event->toLocation` directly (not `$owner->Location`) so it doesn't matter that `EventCardMoved` has `runEventHubAfterCards = true`.

**Why these don't double-fire:** Recruitment in this engine only flips `ControllerId` — the character was already physically sitting at his city-deck location, so no `EventCardMoved` is queued during recruitment. I verified by reading `EventHub.php`'s `EventCharacterRecruited` branch — no nested move event creation. So the two triggers are clean disjoint paths.

**Why post-tense `EventCardMoved` and not `EventCardMoving`:** Card text says "moves to" (post-fact). A move that gets canceled mid-flight (cancelable on `EventCardMoving`) shouldn't trigger Julius. `EventCardMoved` fires after the move sticks.

**`isAvailable()` gate:** Both branches check it before queuing the reaction transition. Once `setUsed(true)` runs on a successful resolution (target picked) or decline, further triggers in the same day no-op. Resets at `EventDuskEndOfDay` via the inherited `CardReaction::handleEvent`.

## Reveal mechanism

Modeled on `_01098` (Cat's Embargo) — that's the only existing "random card from hand" precedent:

```
$hand = array_values($deck->getCardsInLocation(Game::LOCATION_HAND, $target->ControllerId));
$count = min(2, count($hand));
if ($count > 0) {
    $keys = (array)array_rand($hand, $count);
    foreach ($keys as $key) {
        $card = $game->getCardObjectFromDb($hand[$key]['id']);
        $game->theah->addCardToWorld($card);
        // notify with card_inject_code + card payload
        if ($card->hasTrait($this->chosenTrait)) $matched = true;
    }
}
```

`array_rand($hand, $count)` returns a single scalar key when `$count === 1` and an array otherwise — `(array)` cast normalizes that. `addCardToWorld` is what makes the inject code render in the client (per `_01098`). The trait check is just `Card::hasTrait($name)` which is `in_array($name, $this->ModifiedTraits)`, so plain English trait strings from `TraitNames` compare directly against the `clienttranslate()`-wrapped strings stored on each card (clienttranslate is a no-op at runtime).

**Partial-reveal semantics:** Hand has 0 → emit "empty hand" message, no wound (matched stays false). Hand has 1 → reveal that one, check trait, wound if match. Hand has 2+ → reveal exactly 2. Card text says "reveal two cards" — I'm reading that as "up to two." If rules judge says "if you can't reveal two, do nothing," that'd be a one-line change.

## Wound event

`EventFactory::createCharacterBeingWoundedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id)`. The `BeingWounded` flavor is the standard wounding entrypoint that engine handlers turn into actual wounds (it's also what other cards listen to for "would be wounded" preventive effects, but here we want the wound to actually land).

## Pre-commit hook satisfaction

CardReaction subclasses need `$this->setUsed(` AND `$this->isAvailable(` to appear in the file. They do — `setUsed(true)` on both the decline path and the target-resolved path; `isAvailable()` gates both `handleEvent` branches.

## What I deliberately did not do

- No location filter on "opposing character." Card text says "an opposing character" with no location restriction, so I used `$theah->getCharactersInPlay()` and filtered by `ControllerId != owner->ControllerId`. (Considered `getCharactersInCity()` but that excludes characters at home — the printed card has no such limit.)
- No "performer not engaged" check on Julius — this is a Reaction, not an Action; engagement isn't a cost.
- No JS wiring. If button-based reactions stop working visually for this card, the issue is in the framework's `playerReaction` state, not in card-specific code.

## Open / fragile

- If Julius is destroyed between trigger and resolution, `$owner` still exists in `cards[]` but `getOwningCharacter()` may return him in an odd state. The wound still uses his id as `sourceId` — engine handles dead sources gracefully in similar cards. Not a known issue but untested.
- The reaction's private `$stage`/`$chosenLetter`/`$chosenTrait` fields rely on the framework serializing the `Reactions[]` array along with the card across actions. `Reaction_01014` does the same thing with private fields and works in prod, so I'm trusting the pattern.
