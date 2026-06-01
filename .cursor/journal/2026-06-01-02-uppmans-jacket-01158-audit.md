# Uppman's Jacket (_01158) Audit

## Card Text
> **Action:** Discard a card • Draw a card.

FactionAttachment. WealthCost 1. Stats: R0/C0/F0/I+1. P/T 2/2 with dashed Riposte. Traits: Attire, Coat, Rilasciare.

## Component Inventory
- `modules/php/cards/_7s5s/_01158.php` — the card class (`FactionAttachment`, uses `ActionTrait`, implements `IHasActions`).
- `modules/php/cards/_7s5s/actions/Action_01158.php` — the Action (`AttachmentAction`).
- `modules/php/States/_7s5s/State_highDramaPhase01158.php` — single-step "choose card from hand" state.
- `modules/php/States.php:250` — `HIGH_DRAMA_PLAYER_TURN_01158 = 401158`. Matches the `4 + cardId` convention.
- `states.inc.php:934` — transition `"01158" => HIGH_DRAMA_PLAYER_TURN_01158` registered.
- Frontend handlers in `OnEnteringState.7s5s.js:1923`, `OnLeavingState.7s5s.js:1181`, `OnUpdateActionButtons.7s5s.js:724`, `EventHandlers.js:497`. Single-select on factionHand + Confirm Selection button. Standard pattern; matches other "discard-one-from-hand" cards (01091, 01095b, 01102, 01175, 01185, etc.).

## Flow (Action_01158)
1. `isAvailableToPlayer` defers to `AttachmentAction::isAvailableToPlayer` (which requires `getOwningCharacter != null`, i.e. the Jacket is attached and in play, and the user is the controller) and also requires `count($hand) > 0`. ✓
2. `handleEvent` on `EventActionTriggered` queues a transition to state `01158`. ✓
3. `actFromActionWithId` validates control + location of the chosen hand card, then queues `CardDiscardedFromHand` → `CardDrawn` → `ActionResolved`, then `nextState("cardChosen")`. Order is correct (discard before draw, matching the "•" sequencing). ✓
4. `getRequiredAttachTargetId` not relevant — no `createAttachmentEquippedEvent` call. ✓
5. Pre-commit hook: `extends AttachmentAction` requires `createActionResolvedEvent` — present at line 78. ✓
6. `setUsed`, `resetPlayerPassCount`, `announceAction` are not called from the subclass — correct per CLAUDE.md (handled centrally during action confirmation). ✓

## Issues Found

### 1. (BUG-ish, low) Uses deprecated `\BgaUserException`
Lines 54, 61, 66 in `Action_01158.php`:
```php
throw new \BgaUserException($game->translate("Card not found"));
```
Memory `feedback_deprecated_BgaUserException` flags this — should be `Bga\GameFramework\UserException`. Compare with `Action_01156` which uses the new class correctly. Pure deprecation; functionally identical today but a lurking break when BGA framework drops the old alias.

### 2. (dead code, low) Duplicate fetches in `actFromActionWithId`
Lines 50 and 69 both call `$card = $game->getCardObjectFromDb($id);` — the second assignment is dead. The first assignment's `$card` is still alive (no early return between the validation and line 69), so the second fetch just clobbers it with the same row.

Lines 70 and 74 both call `$owner = $this->getOwningCard($game->theah);` — same pattern. Second is redundant.

Trivial cleanup; no functional impact. Worth noting because it suggests the file was assembled from two snippets without a follow-up pass.

### 3. (style, very low) Concatenated `clienttranslate` in `descriptionMyTurn`
`State_highDramaPhase01158.php:23`:
```php
descriptionMyTurn: clienttranslate('Uppman\'s Jacket') . clienttranslate(': ${you} must choose a card to discard:'),
```
Two separate translatable strings concatenated by PHP. BGA's extraction handles each `clienttranslate` independently, so the translator sees fragments rather than a coherent sentence. Other newer state files (e.g. the 01156 family) use a single `clienttranslate` with the card name baked in. Minor.

### 4. (observation, not a bug) Card text doesn't restrict card type
"Discard a card" allows any card in hand — Risk, faction, anything. The implementation also allows any card in hand. Matches text. ✓

### 5. (observation) "Action:" — not "City Action:"
Per memory `feedback_action_vs_city_action_performer_filter`, plain "Action:" lets the performer be at home or in the city. `AttachmentAction::isAvailableToPlayer` doesn't filter on location, so home-located characters can use this. ✓

### 6. (observation) Zombie path doesn't queue `ActionResolvedEvent`
`State_highDramaPhase01158::zombie` just transitions back to `HIGH_DRAMA_PLAYER_TURN_EVENTS` with no resolved-event emitted. Matches the pattern used by sibling discard states (e.g. 01156, 01175). The action is already marked Used at this point (central confirmation flow), so leaving the rest unresolved is the intended zombie consequence. Probably fine, just noting it.

### 7. (observation) "Rilasciare" Trait
The card declares a `Rilasciare` Trait that exists on no other card in the codebase. The 21-day-old memory `feedback_traitnames_alphabetical` says to register new Traits in `TraitNames::$TraitsJson` — but **`TraitNames` / `Traits.php` does not exist** in the current code (verified via `find` and `grep`). The memory is stale; nothing to register. Leaving the trait string as-is is correct given the current code.

### 8. (text-vs-implementation gap, subtle) "Draw a card" with an empty deck
If the player's deck is empty after discarding (or even before), `createCardDrawnEvent` still fires. The hub-side behavior depends on `EventCardDrawn`'s handler — not Action_01158's concern. Worth noting only because the card text "Draw a card" implies a card actually arrives; if the deck is empty, the player just discarded for nothing. This is consistent with how other draw-effect cards behave in this codebase, and is rules-correct (you can't fail to draw, you just draw nothing).

## Risk Assessment
- **#1** is a real deprecation hit; one-line search-and-replace.
- **#2** is dead code only.
- **#3** is cosmetic.
- **#4–#8** are observations/non-issues.

No gameplay bugs. The card is straightforward and the implementation matches the text faithfully.

## Fixes Applied (this session)

User asked to clean up findings.

- **#1 (deprecated exception):** Replaced `\BgaUserException` with `Bga\GameFramework\UserException` (added `use` import). Three throw sites updated.
- **#2 (dead code):** Removed the duplicate `$card = $game->getCardObjectFromDb($id);` and the second `$owner = $this->getOwningCard($game->theah);` in `actFromActionWithId`. Both were no-op re-fetches of the same object.
- **#3 (concat `clienttranslate`):** Collapsed the two `clienttranslate()` fragments in `descriptionMyTurn` into a single localized string `'Uppman\'s Jacket: ${you} must choose a card to discard:'`.

Did not touch #4–#8 — those were observations, not issues.

## WHY for future-me
- The action is intentionally permissive about *which* card can be discarded — the text says "a card" with no qualifier, so don't add a type filter.
- Discard-before-draw order is load-bearing (matches "•" sequencing). If a future agent "optimizes" by queueing draw first, the player could in theory draw the same card they're about to discard if the deck/discard interact weirdly via some reaction. Keep the order.
- The state has no `getArgs`-supplied id list because the entire faction hand is selectable — the frontend uses `factionHand.setSelectionMode('single')` and relies on standard `argsForState`. Don't add a custom `getArgsFromAction` here; it would just duplicate the default.
- The duplicate `getCardObjectFromDb($id)` and `getOwningCard($game->theah)` are dead-code clones, **not** load-bearing re-reads — both calls return the same object. Safe to delete.
