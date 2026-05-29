# Reaction_01008 — Phase 1: Copy Sorcerer Reactions (02001 only, 03007 deferred)

## What landed

Cesca Avara (`Reaction_01008`) now also copies Sorcerer **Reactions** — currently just `Reaction_02001` (Adriana's Wound Non-Sorcerer). Phase 2 will add `Reaction_03007` (Matushka's Shears) once the user merges the branch containing that card. Plan file: `~/.claude/plans/for-reaction-01008-there-is-fuzzy-sloth.md`.

Changes:
- `modules/php/cards/ReactionTrait.php` — added `addReaction()` / `removeReaction()`, mirroring `ActionTrait::addAction` / `removeAction`. Default `notify=false` (silent) because the copy is immediately driven through its own reaction state — the player sees the reaction's buttons, not a "you gained a reaction" toast. Caller can pass `true` if a frontend notify handler is added later.
- `modules/php/cards/_7s5s/reactions/Reaction_01008.php`:
  - New field `$sourceTargetId` (captured from `$event->targetId` on both trigger branches).
  - New field `$copiedReactions` + teardown in the `EventPlayerTurnEnd` branch.
  - New `Reaction_02001` branch in `performReaction` that instantiates a fresh reaction, `setOwnerId(cesca)`, sets `CharacterId = $this->sourceTargetId`, hosts via `addReaction`, and queues a reaction-transition.
  - New `isCopyable(?ICardAbility)` private helper, gating both `handleEvent` trigger branches.

## WHY the host-a-copy approach (vs forking a state machine)

We considered three approaches; the user picked the most ambitious one — host a transient reaction on Cesca and reuse its own `performReaction`. Key facts that made it viable:

- `ReactionTrait::$Reactions` is a plain list and `getReactionById($id)` iterates it — appending at runtime is safe. (`ReactionTrait.php:48-58`)
- `setOwnerId($cardId)` mutates the reaction's `$Id` to `"{cardId}_{ClassId}"` (`CardAbilityTrait:46-50`), so the copy has a unique Id discoverable on Cesca's card.
- Reaction dispatch is `actReactionForState` → `$card->reactionFromCard($internalId)` → `getReactionById($internalId)->performReaction(...)` (`FrameworkActionsTrait.php:1901-1941`, `ReactionTrait:73-77`). Routing the transition with `sourceId = Cesca's card Id` + `internalId = copy Id` lands on the copy.
- Cards (with their `$Reactions` array and each reaction's private state) are PHP-`serialize()`d to the DB. `IsUpdated = true` triggers the save. So the copy survives multi-request flows the same way `copiedActions` already do.

Alternative considered and rejected: forking the reaction's stage machine into a new sub-state in Cesca. That duplicates logic and drifts; the host-a-copy approach reuses 02001's `performReaction` verbatim.

## WHY the `isCopyable` allow-list

Latent bug we fixed: today's `Reaction_01008` trigger fires for *any* Sorcerer ability matching the location/performer conditions, even if `performReaction` has no copy branch. The wound cost (lines 226-227) runs unconditionally on `copyAbility`, so the player would pay 1 wound for nothing. `isCopyable` is an explicit allow-list of every handled class — exactly the union of the existing `instanceof Action_XXXX` branches plus `Reaction_02001`. It also seeds the deferred refactor toward class-name dispatch.

**Maintenance note:** any new `instanceof` branch in `performReaction` must also be added to `isCopyable`. There's a comment on the method calling this out.

## WHY `$sourceTargetId` (rules-team flag)

Reactions in this codebase do not have a target picker — `IAbilityThatTargetsCharacters` reactions get their target from the triggering event, not a state-based selection. The user chose "wound the same target the original ability wounded" rather than build a picker. Cesca's trigger already requires the target be at her location, so the stored `sourceTargetId` is always valid for Reaction_02001's `CharacterId`.

Caveat: the same character takes a second wound from the copy. Fine per the rules-team interpretation here, but worth confirming if questioned.

## Phase 2 caveats (not started)

- `Reaction_03007`'s `EventSorcererAbilityPlayed` carries `targetId = 0`, so Cesca's trigger only fires when she is the performer (Matushka's Shears attached to Cesca). The trigger event doesn't carry the original "opponent" — Phase 2 must derive it (Cesca's opponent helper TBD).
- 03007 also needs `getOwningAttachment` → `getOwningCard` in its perform/helpers (keep handleEvent's attachment checks untouched), plus a `beginCopy(Game, int $opponentId)` public entry that skips the engage stage.

## Pre-existing oddity noticed (not changed)

For abilities that implement **both** `IAbilityThatTargetsCharacters` and `ISorcererAbility` (e.g. `Action_01076` Blood Mark, and now `Reaction_02001`), Cesca's two `handleEvent` branches could *both* fire for the same ability — each queueing a reaction-transition. This is pre-existing behavior; either the framework dedupes or it's been benign in practice. I did not change it here. Flagged for future investigation.

## Verification status

Manual testing on BGA Studio is the only path (no test runner in this repo). Phase 1 should be checked against:
1. Adriana wounds a non-Sorcerer at Cesca's location → Cesca offered Copy → confirm 02001's Wound/Decline buttons appear and the same target gets the second wound.
2. A Sorcerer ability not in `isCopyable` targets at her location → Cesca is **not** offered Copy (no wasted wound).
3. Existing action copies still work (Sibella, Adriana's Action 02001, Blood Mark, etc.).
4. Cesca's `$Reactions` doesn't accumulate copies across turns.

Pre-commit hook should pass — `Reaction_01008` doesn't implement `ISorcererAbility`, so the Sorcerer event hook doesn't apply.
