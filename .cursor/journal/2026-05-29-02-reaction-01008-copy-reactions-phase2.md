# Reaction_01008 — Phase 2: Copy Reaction_03007 (Matushka's Shears)

## What landed

Phase 2 of the Cesca-copy-reactions plan. Cesca can now also copy `Reaction_03007` (Matushka's Shears — force opposing player to wound Leader or sink 2 cards). Phase 1 (infra + Reaction_02001) was already merged into `faf`.

Changes:
- `modules/php/cards/faf/reactions/Reaction_03007.php`
  - `performReaction`: `$owner = $this->getOwningAttachment(...)` → `$this->getOwningCard(...)`. The five private helpers (`advanceToChoose`, `advanceToNextPick`, `sinkOneFromHand`, `woundLeader`, `finalize`) already take `Card $owner` as a parameter — no internal `getOwningAttachment` calls — so only the entry point needed the change. **`handleEvent` is untouched** (it still uses `getOwningAttachment` + `ownerIsAttached` + `$owner->Engaged`; those checks are specific to the normal Matushka's Shears trigger path and must not run on a Cesca-hosted copy).
  - New public `beginCopy(Game $game, int $opponentId): void` — mirrors the post-Engage portion of the `'engage'` branch in `performReaction` (lines 143-162): sets `opponentId` + `cardsSunk=0`, queues `createSorcererAbilityStartEvent`, then dispatches via the existing `advanceToChoose` (or falls back to `finalize` if there's nothing to force, e.g. opponent has <2 cards AND no leader).
- `modules/php/cards/_7s5s/reactions/Reaction_01008.php`
  - `use` import for `Reaction_03007`.
  - New `Reaction_03007` branch in `performReaction`: instantiates a fresh `Reaction_03007`, `setId("Reaction_03007")` + `setOwnerId($cesca->Id)` (matches the existing pattern for the Reaction_02001 copy added in Phase 1), hosts via `addReaction`, then calls `$copy->beginCopy($game, $opponentId)`.
  - `Reaction_03007` added to `isCopyable` allow-list.

## WHY `getOwningCard` (and only in `performReaction`)

`getOwningCard` is the most general resolver — it returns the attachment for Matushka's Shears (attachments *are* Cards) and Cesca for the hosted copy. All downstream uses (`$owner->ControllerId`, `$owner->Id`, `$owner->getInjectCode()`) work for both shapes.

We deliberately leave `handleEvent`'s attachment-specific checks alone (`ownerIsAttached`, `getOwningAttachment`, `$owner->Engaged`). Those gate the normal Matushka's Shears trigger; they would behave incorrectly for a copy hosted on a Character (which has no `Engaged` field and no concept of being attached). The copy doesn't need them anyway because it's never triggered by `EventCharacterDestroyed` — it's driven directly via `beginCopy`.

## WHY a separate `beginCopy` (rather than reusing the `'engage'` reactionId)

We could have queued a reaction-transition with `reactionId='engage'` and let the copy's own `performReaction` handle 'offer' → 'choose' transitions. Two problems:
1. The `'offer'` stage queues `createCardEngagedEvent` for the owner. That fires `EventCardEngaged` for *Cesca* — semantically wrong and may interact badly with other reactions.
2. The `'offer'` UI prompts the player a second time with `Engage and Force Choice / Pass`. Cesca's player already clicked Copy; a redundant prompt is friction.

`beginCopy` skips the Engage event and the offer-prompt — committing on Cesca's behalf — and queues the SorcererStart event so the copy still emits the same Sorcerer events the original would.

## WHY iterate `loadPlayersBasicInfos()` for the opponent

There is no `getOpponentPlayerId` helper in the codebase. The convention I found (`Action_03cd13.php:191-213`, plus `Reaction_03010` storing `opposingPlayerId` from event data) is: iterate via `loadPlayersBasicInfos()` and filter. The original `Reaction_03007::handleEvent` derives `opponentId` from `EventCharacterDestroyed.playerId` (the controller of the destroyed character), but by the time Cesca's trigger fires, the original reaction has already `finalize`d and `resetStage`d, so we can't read it back from the source.

For **2-player**, this is unambiguous and correct. For **2+ players**, this picks "the first non-Cesca player" — which may not be the original opponent. Flagged in the journal and code comment for rules-team confirmation. Cesca's 03007 trigger is already narrow (only fires when Matushka's Shears is attached to Cesca herself), so the edge case is rare.

## Reachability sanity check

Cesca's trigger fires for an EventSorcererAbilityPlayed if one of these holds (gated also by `isCopyable`):
- `source->Id == cesca->Id` — would mean Matushka's Shears IS Cesca, impossible.
- `event->performerId == cesca->Id` — happens iff Matushka's Shears is attached to Cesca.
- `abilityTargetedCharacterAtHerLocation` — false because 03007's played event has `targetId = 0`.

So in practice the copy path runs when Matushka's Shears is attached to Cesca. That's a real but uncommon configuration. Worth a focused test.

## Persistence note

The copy's `$stage` private field, `$opponentId`, `$cardsSunk` all persist across the multi-request flow because cards are PHP-`serialize()`d to the DB with their full `$Reactions` array (Phase 1 journal covers this). `addReaction` + `IsUpdated=true` triggers the save; `setUsed` (inside `finalize`) also persists. So `beginCopy` → opponent picks 'sink' → next request reloads the copy with `stage='pick1'` correctly.

## Pre-commit hook

`Reaction_03007` already calls `createSorcererAbilityStartEvent` (in `beginCopy` and the existing `'engage'` branch) AND `createSorcererAbilityPlayedEvent` (in `finalize`). Hook should still pass. `Reaction_01008` does not implement `ISorcererAbility`, so the Sorcerer-event hook rule doesn't apply.

## Verification on BGA Studio

1. Configure: Matushka's Shears attached to Cesca; opposing player has ≥2 cards in hand and a Leader.
2. Trigger: opposing character destroyed → Matushka's Shears reaction window for Cesca's controller → click `Engage and Force Choice` → opponent goes through `choose` (`Sink two cards` / `Wound Leader`).
3. After 03007's `finalize` queues SorcererAbilityPlayed, Cesca's trigger should fire → Copy window for Cesca's controller.
4. Click Copy → Cesca wounds 1 (cost) → opponent gets a second `choose` (or wound directly if hand <2) → resolve.
5. End of turn: confirm Cesca's `$Reactions` doesn't include the copy after `EventPlayerTurnEnd`.

Also sanity-test that Matushka's Shears' normal flow still works when NOT attached to Cesca (regression check on the `getOwningAttachment` → `getOwningCard` change in `performReaction`).
